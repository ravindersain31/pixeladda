<?php

namespace App\Controller\Web\MyAccount;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\StoreConfigEnum;
use Symfony\Component\Mime\Address;
use App\Form\PaymentLinkType;
use App\Payment\AmazonPay\AmazonPay;
use App\Payment\Gateway;
use App\Service\CogsHandlerService;
use App\Service\OrderService;
use App\Service\PaymentLinkMailer;
use App\Service\SavedPaymentDetailService;
use App\Service\SlackManager;
use App\SlackSchema\PaymentLinkPaidSchema;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentLinkController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/payment-link/{requestId}', name: 'payment_link')]
    public function paymentLink(Request $request,PaymentLinkMailer $paymentLinkMailer,
        EntityManagerInterface $entityManager, Gateway $gateway, SlackManager $slackManager, CogsHandlerService $cogs, OrderService $orderService,  AmazonPay $amazonPay, SavedPaymentDetailService $savedPaymentDetailService): Response
    {
        $requestId = $request->get('requestId');
        $paymentRequest = $entityManager->getRepository(OrderTransaction::class)->findOneBy(['transactionId' => $requestId]);
        if (!$paymentRequest) {
            $this->addFlash('error', 'Invalid payment request');
            return $this->redirectToRoute('my_account');
        }

        $order = $paymentRequest->getOrder();

        $form = $this->createForm(PaymentLinkType::class, null, [
            'totalAmount' => $paymentRequest->getAmount(),
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $paymentMethod = $form->get('paymentMethod')->getData();
            $paymentNonce = $form->get('paymentNonce')->getData();

            $existingCardToken = null;
            $saveNewCard = false;
            $newSavedToken = null;

            if ($form->has('savedCardToken')) {
                $existingCardToken = $form->get('savedCardToken')->getData();
            }

            if ($form->has('saveCard')) {
                $saveNewCard = (bool) $form->get('saveCard')->getData();
            }

            if ($paymentRequest->getStatus() == PaymentStatusEnum::COMPLETED) {
                $paymentLinkMailer->sendPaymentReceivedEmail($order, $paymentRequest);
            }
            if ($paymentMethod === PaymentMethodEnum::CREDIT_CARD && empty($paymentNonce) && empty($existingCardToken)) {
                $form->get('paymentMethod')->addError(new FormError('Please enter the valid payment details. If this error persists contact support.'));
            } else {

                if (
                    $paymentMethod === PaymentMethodEnum::CREDIT_CARD
                    && $paymentNonce
                    && !$existingCardToken
                    && $saveNewCard
                    && $this->getUser()
                ) {
                    $savedcard = $savedPaymentDetailService->add(
                        $this->getUser(),
                        $paymentNonce
                    );

                    if ($savedcard['success']) {
                        $newSavedToken = $savedcard['data']['token'];
                    }
                }

                $gateway->initialize($paymentMethod, 'USD');
                $gateway->setOrder($order);
                $gateway->setTransaction($paymentRequest);
                $gateway->setStore($this->store);
                $gateway->setCustomAmount($paymentRequest->getAmount());
                $gateway->setActionOnSuccess('REDIRECT_ON_PAYMENT_LINK');

                if ($existingCardToken) {
                    $gateway->setSavedCardToken($existingCardToken);
                } elseif ($newSavedToken) {
                    $gateway->setSavedCardToken($newSavedToken);
                }
                if ($paymentNonce) {
                    $gateway->setPaymentNonce($paymentNonce);
                }
                if ($paymentMethod === PaymentMethodEnum::STRIPE && $paymentNonce) {
                    $gateway->setPaymentIntent($paymentNonce);
                }
                $payment = $gateway->startPayment()->execute();
                if ($payment['success']) {
                    if ($payment['action'] === 'redirect') {
                        return $this->redirect($payment['redirectUrl']);
                    }

                    $cogs->syncPaymentLinkAmount($order->getStore(), $order->getOrderAt());
                    $slackManager->send(SlackManager::SALES, PaymentLinkPaidSchema::get($order, $paymentRequest, [
                        'paymentLink' => $this->generateUrl('payment_link', ['requestId' => $paymentRequest->getTransactionId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'viewOrderLink' => $this->generateUrl('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'proofsLink' => $this->generateUrl('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ]));

                    $order = $orderService->updatePaymentStatus($order);
                    $entityManager->persist($order);
                    $entityManager->flush();

                    // $this->addFlash('success', 'Payment completed successfully.');
                    return $this->redirectToRoute('payment_link', ['requestId' => $requestId]);
                }
                $this->addFlash('danger', $payment['message']);
            }
        }
        $amazonPayData = $amazonPay->getSignature();
        $amazonCheckoutSessionData = null;
        $currentPath = $request->getPathInfo();
        if ($request->get('amazonCheckoutSessionId')) {
            $sessionId = $request->get('amazonCheckoutSessionId');
            $sessionChargeResult = $amazonPay->handleSessionAndCharge($sessionId, $paymentRequest->getAmount(), 'USD', $paymentRequest->getOrder()->getOrderId(), $currentPath);

            if ($sessionChargeResult['success']) {
                $amazonCheckoutSessionData = $sessionChargeResult['data'];
            } else {
                $this->addFlash('danger', $sessionChargeResult['message'] ?? 'Invalid Payment Details');
                return $this->redirectToRoute('payment_link', ['requestId' => $requestId]);
            }
        }

        $amazonPayCheckoutData = [
            'signature' => $amazonPayData['signature'] ?? null,
            'payload' => $amazonPayData['payload'] ?? null,
            'returnUrl' => $amazonPayData['checkoutResultReturnUrl'] ?? null,
            'checkoutSession' => $amazonCheckoutSessionData,
        ];

        $savedCards = [];
        if($this->getUser()) {
            $savedCards = $savedPaymentDetailService->getSavedPaymentDetails($this->getUser()); 
        }

        return $this->render('account/payment-link.html.twig', [
            'form' => $form->createView(),
            'order' => $order,
            'paymentRequest' => $paymentRequest,
            'amazonPay' => $amazonPayCheckoutData,
            'savedCards' => $savedCards,
        ]);
    }

    #[Route(path: '/payment-link/{requestId}/restart', name: 'payment_link_restart_payment')]
    public function paymentLinkRestart(Request $request, EntityManagerInterface $entityManager, Gateway $gateway): Response
    {
        $requestId = $request->get('requestId');
        $paymentRequest = $entityManager->getRepository(OrderTransaction::class)->findOneBy(['transactionId' => $requestId]);
        if (!$paymentRequest) {
            $this->addFlash('error', 'Invalid payment request');
            return $this->redirectToRoute('my_account');
        }

        if (in_array($paymentRequest->getStatus(), [PaymentStatusEnum::PENDING_CAPTURE, PaymentStatusEnum::REDIRECTED_TO_GATEWAY])) {
            $paymentRequest->setStatus(PaymentStatusEnum::INITIATED);
            $entityManager->persist($paymentRequest);
            $entityManager->flush();
            $this->addFlash('success', 'You can now choose another payment option.');
            return $this->redirectToRoute('payment_link', ['requestId' => $requestId]);
        }

        if (in_array($paymentRequest->getStatus(), [PaymentStatusEnum::INITIATED])) {
            $this->addFlash('info', 'This payment link is already in a state where you can choose the payment option.');
        } else {
            $this->addFlash('danger', 'You can\'t change the payment option for this payment link. As it was already completed, or invalid');
        }
        return $this->redirectToRoute('payment_link', ['requestId' => $requestId]);
    }
}
