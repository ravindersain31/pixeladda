<?php

namespace App\Controller\Web\Payment;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderProofApprovedEvent;
use App\Event\OrderReceivedEvent;
use App\Payment\AmazonPay\AmazonPay;
use App\Payment\Gateway;
use App\Service\CartManagerService;
use App\Service\OrderService;
use App\Service\SlackManager;
use App\SlackSchema\NewProofUploadedSchema;
use App\SlackSchema\PaymentDeclineSchema;
use App\SlackSchema\PaymentLinkPaidSchema;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\PaymentLinkMailer;
use App\SlackSchema\OrderApprovedSchema;
use Symfony\Component\Mailer\MailerInterface;

class AmazonPayRedirectController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/payment/amazonpay/return/{orderId}/{path}', name: 'amazonpay_result',methods: ['GET', 'POST'], defaults: ['orderId' => 0], requirements: ['path' => '.+'])]
    public function amazonPayReturn(
        string $orderId,
        Request $request,
        Gateway $gateway,
        OrderService $orderService,
        SlackManager $slackManager,
        CartManagerService $cartManagerService,
        AmazonPay $amazonPay,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        PaymentLinkMailer $paymentLinkMailer,
        UrlGeneratorInterface $urlGenerator,
        ?string $path = null,
    ): Response {

        $segments = explode('/', $path ?? '');
        $type = $segments[0] ?? null;
        $token = $segments[1] ?? null;
        $amazonCheckoutSessionId = $request->query->get('amazonCheckoutSessionId');

        if (!$amazonCheckoutSessionId) {
            $this->addFlash('error', 'Missing Amazon Checkout Session ID');
            return $this->redirectToRoute('cart');
        }

        $cart = $cartManagerService->getCart();
        $sessionResult = $amazonPay->getSession($amazonCheckoutSessionId);
        $sessionData = json_decode($sessionResult['response'] ?? '{}');

        if (!isset($sessionData->buyer, $sessionData->shippingAddress, $sessionData->billingAddress)) {
            $this->addFlash('error', 'Invalid Amazon Pay session data');
            return $this->redirectToRoute('cart');
        }

        $buyer = $sessionData->buyer;
        $shippingAddress = $this->makeAmazonOrderAddress($sessionData->shippingAddress, $buyer->email);
        $billingAddress = $this->makeAmazonOrderAddress($sessionData->billingAddress, $buyer->email);
        if ($type == 'payment-link') {
            $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);
            $orderTransaction = $entityManager->getRepository(OrderTransaction::class)->findOneBy([
                'order' => $order,
                'transactionId' => $token,
            ]);
            $orderTransaction->setPaymentMethod(PaymentMethodEnum::AMAZON_PAY);
            $gateway->initialize(PaymentMethodEnum::AMAZON_PAY, 'USD');
            $gateway->setCustomAmount($orderTransaction->getAmount());
            $gateway->setActionOnSuccess('REDIRECT_ON_PAYMENT_LINK');
            $gateway->setOrder($order);
            $gateway->setTransaction($orderTransaction);
            $gateway->setStore($this->store);
            $gateway->setPaymentData([
                'amazonPaySessionId' => $amazonCheckoutSessionId,
            ]);
            $payment = $gateway->startPayment()->execute();
            if ($order->getPaymentLinkAmount() > 0 || $order->getTotalAmount() == $order->getPaymentLinkAmountReceived()) {
                $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
            } elseif ($order->getPaymentLinkAmount() > 0 && $order->getTotalAmount() != $order->getPaymentLinkAmountReceived()) {
                $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
            } else {
                $order->setPaymentStatus(PaymentStatusEnum::PENDING);
            }
            if ($order->getPaymentStatus() == PaymentStatusEnum::COMPLETED) {
                $paymentLinkMailer->sendPaymentReceivedEmail($order, $orderTransaction);
            }

            if ($payment['success']) {
                $cartManagerService->issueNewCart();
                $slackManager->send(SlackManager::SALES, PaymentLinkPaidSchema::get($order, $orderTransaction, [
                    'paymentLink' => $this->generateUrl('payment_link', ['requestId' => $orderTransaction->getTransactionId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'viewOrderLink' => $this->generateUrl('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'proofsLink' => $this->generateUrl('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ]));

                $session->set('newOrder', true);
                $session->set('orderId', $order->getOrderId());

                return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
            }
            $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], [
                'paymentLink' => $this->generateUrl('payment_link', ['requestId' => $orderTransaction->getTransactionId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]));
        }
        elseif ($type == 'order_proof_approve') {
            $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);
            $amount = $order->getTotalAmount() - $order->getTotalReceivedAmount();
            if ($order->getTotalReceivedAmount() > 0) {
                $order->setTotalAmount($amount);
                $gateway->initialize(PaymentMethodEnum::AMAZON_PAY, 'USD');
                $gateway->setOrder($order);
                $gateway->setStore($this->store);
                $gateway->setPaymentData([
                    'amazonPaySessionId' => $amazonCheckoutSessionId,
                ]);
            }
            $gateway->initialize(PaymentMethodEnum::AMAZON_PAY, 'USD');
            $gateway->setOrder($order);
            $gateway->setStore($this->store);
            $gateway->setActionOnSuccess('APPROVE_PROOF');
            $gateway->setPaymentData([
                'amazonPaySessionId' => $amazonCheckoutSessionId,
            ]);
            $payment = $gateway->startPayment()->execute();
            $receivedAmount = $order->getTotalReceivedAmount();
            $order->setTotalAmount($receivedAmount);
            $order->setStatus(OrderStatusEnum::PROOF_APPROVED);

            $slackManager->send(SlackManager::ORDER_APPROVED, OrderApprovedSchema::get($order, $urlGenerator));

            if ($payment['success']) {
                $cartManagerService->issueNewCart();
                $approvedProof = $entityManager->getRepository(OrderMessage::class)->getLastProofMessage($order);
                $slackManager->send(SlackManager::DESIGNER, NewProofUploadedSchema::get($order, $approvedProof, [
                    'customerProofLink' => $this->generateUrl('order_proof', ['oid' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'viewOrderLink' => $this->generateUrl('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'proofsLink' => $this->generateUrl('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ]));
                $session->set('newOrder', true);
                $session->set('orderId', $order->getOrderId());

                return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
            }
        }else {
            $order = $orderService->startOrder($cart,$this->store);
            $order->setBillingAddress($billingAddress);
            $order->setShippingAddress($shippingAddress);
            $order->setPaymentMethod(PaymentMethodEnum::AMAZON_PAY);
            $order->setAgreeTerms(true);
            $order->setTextUpdates(true);
            $order->setTextUpdatesNumber($billingAddress['phone']);
            $orderService->setItems($cart->getCartItems());
            $order = $orderService->endOrder();
            $gateway->initialize(PaymentMethodEnum::AMAZON_PAY, 'USD');
            $gateway->setOrder($order);
            $gateway->setStore($this->store);
            $gateway->setPaymentData([
                'amazonPaySessionId' => $amazonCheckoutSessionId,
            ]);
            $payment = $gateway->startPayment()->execute();
            if ($payment['success']) {
                $cartManagerService->issueNewCart();
                $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);
                $session->set('newOrder', true);
                $session->set('orderId', $order->getOrderId());
                return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
            }
        }
        $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], [
            'viewOrderLink' => $this->generateUrl('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'proofsLink' => $this->generateUrl('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]));

        $this->addFlash('error', 'Amazon Pay payment failed: ' . $payment['message']);
        return $this->redirectToRoute('cart'); 
    }

    private function makeAmazonOrderAddress(object $amazonAddress, string $email): array
    {
        $name = $this->convertFullNameToFirstNameLastName($amazonAddress->name ?? '');

        return [
            'firstName' => $name[0] ?? '',
            'lastName' => $name[1] ?? '',
            'addressLine1' => $amazonAddress->addressLine1 ?? '',
            'addressLine2' => trim(($amazonAddress->addressLine2 ?? '') . ' ' . ($amazonAddress->addressLine3 ?? '')),
            'city' => $amazonAddress->city ?? '',
            'state' => $amazonAddress->stateOrRegion ?? '',
            'country' => $amazonAddress->countryCode ?? '',
            'zipcode' => $amazonAddress->postalCode ?? '',
            'email' => $email,
            'phone' => $amazonAddress->phoneNumber ?? '',
        ];
    }

    private function convertFullNameToFirstNameLastName(string $fullName): array
    {
        $parts = explode(" ", $fullName);
        if (count($parts) > 1) {
            $lastname = array_pop($parts);
            $firstname = implode(" ", $parts);
        } else {
            $firstname = $fullName;
            $lastname = " ";
        }
        return [$firstname, $lastname];
    }

}
