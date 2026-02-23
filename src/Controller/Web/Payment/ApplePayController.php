<?php

namespace App\Controller\Web\Payment;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\PaymentMethodEnum;
use App\Event\OrderReceivedEvent;
use App\Payment\Gateway;
use App\Service\CartManagerService;
use App\Service\CogsHandlerService;
use App\Service\OrderService;
use App\Service\SlackManager;
use App\SlackSchema\PaymentDeclineSchema;
use App\SlackSchema\PaymentLinkPaidSchema;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApplePayController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/payment/apple-pay/checkout', name: 'apple_pay_checkout', methods: ['POST'])]
    public function applePayCheckout(
        Request $request,
        Gateway $gateway,
        OrderService $orderService,
        SessionInterface $session,
        SlackManager $slackManager,
        CogsHandlerService $cogs,
        CartManagerService $cartManagerService,
        EntityManagerInterface $em,
    ): Response {
        $paymentNonce = $request->get('paymentNonce');
        $shippingContact = $request->get('shippingAddress');
        $email = $request->get('email');
        $orderId = $request->get('orderId');
        $transactionId = $request->get('paymentLink');
        $formAction = $request->get('formAction');
        $formData = $request->get('formData');
        $isExpress = $request->get('isExpress');

        if($isExpress){
            return $this->handleExpressApplePay($paymentNonce, $shippingContact, $email, $cartManagerService, $orderService, $gateway, $session, $slackManager);
        }

        switch ($formAction) {
            case 'checkout':
                return $this->handleCheckoutFlow($formData, $paymentNonce, $cartManagerService, $orderService, $gateway, $session, $slackManager);

            case 'approve_proof':
                return $this->handleApproveProofFlow($em, $paymentNonce, $orderId, $gateway, $cogs, $slackManager);

            case 'payment_link':
                return $this->handlePaymentLinkFlow($em, $paymentNonce, $transactionId, $gateway, $slackManager, $orderService, $cogs);

            default:
                return $this->json(['success' => false, 'message' => 'Invalid form action.'], 400);
        }
    }

    private function handleCheckoutFlow(
        array $formData,
        string $paymentNonce,
        CartManagerService $cartManagerService,
        OrderService $orderService,
        Gateway $gateway,
        SessionInterface $session,
        SlackManager $slackManager
    ): JsonResponse {
        $shippingAddress = $this->makeFormAddress($formData['checkout']['shippingAddress'] ?? []);
        $billingAddress = $this->makeFormAddress($formData['checkout']['billingAddress'] ?? []);

        $cart = $cartManagerService->getCart();
        $order = $orderService->startOrder($cart, $this->store);
        $order->setShippingAddress($shippingAddress);
        $order->setBillingAddress($billingAddress);
        $order->setPaymentMethod(PaymentMethodEnum::APPLE_PAY);
        $order->setAgreeTerms(true);
        $order->setTextUpdates(true);
        $order->setTextUpdatesNumber($billingAddress['phone'] ?? '');
        $orderService->setItems($cart->getCartItems());
        $order = $orderService->endOrder();

        $gateway->initialize(PaymentMethodEnum::APPLE_PAY, 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $gateway->setPaymentNonce($paymentNonce);

        $payment = $gateway->startPayment()->execute();

        if ($payment['success']) {
            $cartManagerService->issueNewCart();
            $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);
            $session->set('newOrder', true);
            $session->set('orderId', $order->getOrderId());

            return $this->json([
                'success' => true,
                'message' => 'Order created and payment successful.',
                'order_id' => $order->getOrderId(),
                'redirect_url' => $this->generateUrl('order_view', ['oid' => $order->getOrderId()])
            ]);
        }

        $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], $this->getSlackLinks($order)));

        return $this->json([
            'success' => false,
            'message' => $payment['message'] ?? 'Payment failed. Please try another payment method.',
        ], 500);
    }

    private function handleApproveProofFlow(
        EntityManagerInterface $em,
        string $paymentNonce,
        string $orderId,
        Gateway $gateway,
        CogsHandlerService $cogs,
        SlackManager $slackManager
    ): JsonResponse {
        $order = $em->getRepository(Order::class)->findOneBy(['orderId' => $orderId, 'store' => $this->getStore()->id]);
        if (!$order instanceof Order) {
            return $this->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $order->setPaymentMethod(PaymentMethodEnum::APPLE_PAY);
        $gateway->initialize(PaymentMethodEnum::APPLE_PAY, 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $gateway->setActionOnSuccess('APPROVE_PROOF');
        $gateway->setPaymentNonce($paymentNonce);

        $receivedAmount = $order->getTotalReceivedAmount();
        $totalAmount = $order->getTotalAmount();
        $refundedAmount = $order->getRefundedAmount();

        $dueAmount = round($totalAmount + $refundedAmount - $receivedAmount, 2);

        if ($dueAmount > 0) {
            $gateway->setCustomAmount($dueAmount);
        }

        $payment = $gateway->startPayment()->execute();

        if ($payment['success']) {
            $cogs->syncOrderSales($order->getStore(), $order->getOrderAt());

            return $this->json([
                'success' => true,
                'message' => 'Thank you for approving your proof and completing payment.',
                'order_id' => $order->getOrderId(),
                'redirect_url' => $this->generateUrl('order_view', ['oid' => $order->getOrderId()])
            ]);
        }

        $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], $this->getSlackLinks($order)));

        return $this->json([
            'success' => false,
            'message' => $payment['message'] ?? 'Payment failed. Please try again.',
        ], 500);
    }

    private function handlePaymentLinkFlow(
        EntityManagerInterface $em,
        string $paymentNonce,
        string $transactionId,
        Gateway $gateway,
        SlackManager $slackManager,
        OrderService $orderService,
        CogsHandlerService $cogs
    ): JsonResponse {
        $orderTransaction = $em->getRepository(OrderTransaction::class)->findOneBy(['transactionId' => $transactionId]);
        $order = $orderTransaction?->getOrder();

        if (!$orderTransaction || !$order) {
            return $this->json(['success' => false, 'message' => 'Invalid transaction.'], 404);
        }

        $gateway->initialize(PaymentMethodEnum::APPLE_PAY, 'USD');
        $gateway->setOrder($order);
        $gateway->setTransaction($orderTransaction);
        $gateway->setStore($this->store);
        $gateway->setCustomAmount($orderTransaction->getAmount());
        $gateway->setActionOnSuccess('REDIRECT_ON_PAYMENT_LINK');
        $gateway->setPaymentNonce($paymentNonce);
        
        $payment = $gateway->startPayment()->execute();

        if ($payment['success']) {
            $cogs->syncPaymentLinkAmount($order->getStore(), $order->getOrderAt());
            $slackManager->send(SlackManager::SALES, PaymentLinkPaidSchema::get($order, $orderTransaction, array_merge(
                $this->getSlackLinks($order),
                [
                    'paymentLink' => $this->generateUrl('payment_link', ['requestId' => $transactionId], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            )));

            $order = $orderService->updatePaymentStatus($order);
            $em->persist($order);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Payment completed successfully.',
                'order_id' => $order->getOrderId(),
                'redirect_url' => $this->generateUrl('payment_link', ['requestId' => $transactionId])
            ]);
        }

        $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], $this->getSlackLinks($order)));

        return $this->json([
            'success' => false,
            'message' => $payment['message'] ?? 'Payment failed. Please try another payment method.',
        ], 500);
    }

    private function handleExpressApplePay(
        ?string $paymentNonce,
        array $shippingContact,
        ?string $email,
        CartManagerService $cartManagerService,
        OrderService $orderService,
        Gateway $gateway,
        SessionInterface $session,
        SlackManager $slackManager
    ): JsonResponse {
        $billingAddress = $this->makeAppleAddress($shippingContact, $email);
        $shippingAddress = $this->makeAppleAddress($shippingContact, $email);

        $cart = $cartManagerService->getCart();

        $order = $orderService->startOrder($cart, $this->store);
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
        $order->setPaymentMethod(PaymentMethodEnum::APPLE_PAY);
        $order->setAgreeTerms(true);
        $order->setTextUpdates(true);
        $order->setTextUpdatesNumber($billingAddress['phone'] ?? '');

        $orderService->setItems($cart->getCartItems());
        $order = $orderService->endOrder();

        $gateway->initialize(PaymentMethodEnum::APPLE_PAY, 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $gateway->setPaymentNonce($paymentNonce);
        
        $payment = $gateway->startPayment()->execute();

        if ($payment['success']) {
            $cartManagerService->issueNewCart();
            $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);            
            $session->set('newOrder', true);
            $session->set('orderId', $order->getOrderId());

            return $this->json([
                'success' => true,
                'message' => 'Order created and payment successful.',
                'order_id' => $order->getOrderId(),
                'redirect_url' => $this->generateUrl('order_view', ['oid' => $order->getOrderId()])
            ]);
        }

        $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], [
            'viewOrderLink' => $this->generateUrl('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'proofsLink' => $this->generateUrl('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]));

        $slackManager->send(SlackManager::CSR_DECLINES, PaymentDeclineSchema::get($order, $payment['message'], $this->getSlackLinks($order)));

        return $this->json([
            'success' => false,
            'message' => $payment['message'] ?? 'Payment failed. Please try another payment method.',
            'action' => 'showMessage',
        ]);
    }

    private function makeFormAddress(array $address): array
    {
        return [
            'firstName'    => $address['firstName'] ?? '',
            'lastName'     => $address['lastName'] ?? '',
            'addressLine1' => $address['addressLine1'] ?? '',
            'addressLine2' => $address['addressLine2'] ?? '',
            'city'         => $address['city'] ?? '',
            'state'        => $address['state'] ?? '',
            'country'      => $address['country'] ?? '',
            'zipcode'      => $address['zipcode'] ?? '',
            'email'        => $address['email'] ?? '',
            'phone'        => $address['phone'] ?? '',
        ];
    }

    private function makeAppleAddress(array $applePayContact, string $email): array
    {
        $name = $this->parseApplePayName($applePayContact['givenName'] ?? '', $applePayContact['familyName'] ?? '');
        
        return [
            'firstName' => $name[0],
            'lastName' => $name[1],
            'addressLine1' => $applePayContact['addressLines'][0] ?? '',
            'addressLine2' => implode(' ', array_slice($applePayContact['addressLines'] ?? [], 1)),
            'city' => $applePayContact['locality'] ?? '',
            'state' => $applePayContact['administrativeArea'] ?? '',
            'country' => $applePayContact['countryCode'] ?? 'US',
            'zipcode' => $applePayContact['postalCode'] ?? '',
            'email' => $email,
            'phone' => $applePayContact['phoneNumber'] ?? '',
        ];
    }

    private function parseApplePayName(string $givenName, string $familyName): array
    {
        if (empty($givenName) && empty($familyName)) {
            return ['', ''];
        }
        
        if (empty($givenName)) {
            return [$familyName, ''];
        }
        
        if (empty($familyName)) {
            return [$givenName, ''];
        }
        
        return [$givenName, $familyName];
    }

    private function getSlackLinks(Order $order): array
    {
        return [
            'viewOrderLink' => $this->generateUrl('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'proofsLink' => $this->generateUrl('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }
}