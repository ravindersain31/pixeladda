<?php

namespace App\Controller\Web\Payment;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderProofApprovedEvent;
use App\Event\OrderReceivedEvent;
use App\Payment\Paypal\Capture;
use App\Service\CartManagerService;
use App\Service\CogsHandlerService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaypalRedirectController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/payment/paypal/return/{orderId}', name: 'paypal_return', defaults: ['orderId' => 0])]
    public function paypalReturn(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, Request $request, Capture $capture, EntityManagerInterface $entityManager , CartManagerService $cartManagerService, CogsHandlerService $cogs, UrlGeneratorInterface $urlGenerator): Response
    {
        $token = $request->get('token');
        $payerId = $request->get('PayerID');
        $actionOnSuccess = $request->get('actionOnSuccess');

        $capture->setOrder($order);
        $capture->setToken($token);
        $capture->setPayerId($payerId);

        $transaction = $entityManager->getRepository(OrderTransaction::class)->findOneBy(['gatewayId' => $token]);
        $capture->setTransaction($transaction);
        $response = $capture->execute();
        if ($response['success']) {

            if ($transaction->isIsPaymentLink()) {
                $order->setPaymentLinkAmountReceived($order->getPaymentLinkAmountReceived() + $transaction->getAmount());
                $order->setTotalReceivedAmount(floatval($order->getTotalReceivedAmount()) + floatval($transaction->getAmount()));
                
                $totalAmount = round($order->getTotalAmount(), 2);
                $totalReceivedAmount = round($order->getTotalReceivedAmount(), 2);
                
                if ($totalReceivedAmount >= $totalAmount) {
                    $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
                } else {
                    $order->setPaymentStatus(PaymentStatusEnum::PENDING);
                }
                
                $entityManager->persist($order);
                $entityManager->flush();
            }

            if ($actionOnSuccess) {
                if (strtoupper($actionOnSuccess) === 'APPROVE_PROOF') {
                    $approvedProof = $entityManager->getRepository(OrderMessage::class)->getLastProofMessage($order);
                    $this->eventDispatcher->dispatch(new OrderProofApprovedEvent($order, $approvedProof), OrderProofApprovedEvent::NAME);
                    $order->setApprovedProof($approvedProof);
                    $order->setProofApprovedAt(new \DateTimeImmutable());
                    $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                    $order->setIsApproved(true);

                    $entityManager->persist($order);
                    $entityManager->flush();


                    $response['message'] = 'Thank you for approving your proof and completing payment. We will begin processing your order immediately. <br/>Thank you for choosing Yard Sign Plus.';
                    $cogs->syncOrderSales($order->getStore(), $order->getOrderAt());
                } else if (strtoupper($actionOnSuccess) === 'REDIRECT_ON_PAYMENT_LINK') {
                    $this->addFlash('success', $response['message']);
                    $cogs->syncPaymentLinkAmount($order->getStore(), $order->getOrderAt());
                    return $this->redirectToRoute('payment_link', ['requestId' => $transaction->getTransactionId()]);
                }
            } else {
                $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);
                $cartManagerService->issueNewCart();
            }
            $this->addFlash('success', $response['message']);
        } else {
            $this->addFlash('danger', $response['message']);
        }
        return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
    }

    #[Route(path: '/payment/paypal/cancel/{orderId}', name: 'paypal_cancel', defaults: ['orderId' => 0])]
    public function paypalCancel(string $orderId, Request $request, EntityManagerInterface $entityManager, CartManagerService $cartManagerService): Response
    {
        $token = $request->get('token');
        $actionOnSuccess = $request->get('actionOnSuccess');

        $this->addFlash('danger', 'Payment cancelled');

        $transaction = $entityManager->getRepository(OrderTransaction::class)->findOneBy(['gatewayId' => $token]);
        $transaction->setStatus(PaymentStatusEnum::CANCELLED);
        $transaction->setMetaDataKey('cancelledAt', new \DateTimeImmutable());
        $entityManager->persist($transaction);
        $entityManager->flush();
        if ($actionOnSuccess) {
            if (strtoupper($actionOnSuccess) === 'REDIRECT_ON_PAYMENT_LINK') {
                return $this->redirectToRoute('payment_link', ['requestId' => $transaction->getTransactionId()]);
            } else if (strtoupper($actionOnSuccess) === 'APPROVE_PROOF') {
                return $this->redirectToRoute('order_proof_approve', ['oid' => $orderId]);
            }
        }

        return $this->redirectToRoute('checkout');
    }
}