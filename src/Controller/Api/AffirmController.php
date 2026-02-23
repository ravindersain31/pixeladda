<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Event\OrderReceivedEvent;
use App\Helper\VichS3Helper;
use App\Payment\Affirm\Affirm;
use App\Payment\Gateway;
use App\Service\CartManagerService;
use App\Service\CogsHandlerService;
use App\Service\OrderLogger;
use App\Service\OrderService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AffirmController extends AbstractController
{
    use StoreTrait;

    #[Route('/affirm/initiate', name: 'affirm_initiate', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        CartManagerService $cartManagerService,
        Affirm $affirm
    ): JsonResponse {

        $formAction = $request->get('action');
        $orderId = $request->get('orderId');
        $paymentLink = $request->get('paymentLink');
        $checkout = $request->get('checkout');
        
        if ($formAction === 'checkout') {
            $cart = $cartManagerService->getCart();
            $shippingAddress = $this->makeAddressFromCheckout($checkout['shippingAddress'] ?? []);
            $billingAddress = $this->makeAddressFromCheckout($checkout['billingAddress'] ?? []);
            $payload = $affirm->payloadBuild(cart: $cart, shippingAddress: $shippingAddress, billingAddress: $billingAddress, formAction: $formAction);            
        } else if ($formAction === 'payment_link') {
            $order = $entityManager->getRepository(Order::class)->findOneBy([
                'orderId' => $orderId,
                'store' => $this->getStore()->id,
            ]);

            $orderTransaction = $entityManager->getRepository(OrderTransaction::class)->findOneBy([
                'order' => $order,
                'transactionId' => $paymentLink,
            ]);

            if ($orderTransaction instanceof OrderTransaction) {
                $shippingAddress = $order->getShippingAddress() ?? [];
                $billingAddress = $order->getBillingAddress() ?? [];
                $payload = $affirm->payloadBuild(order: $order, cart: $order->getCart(), shippingAddress: $shippingAddress, billingAddress: $billingAddress, formAction: $formAction, orderTransaction: $orderTransaction); 
            }
        } else if ($formAction === 'approve_proof') {
            $order = $entityManager->getRepository(Order::class)->findOneBy([
                'orderId' => $orderId,
                'store' => $this->getStore()->id,
            ]);

            if ($order instanceof Order) {
                $shippingAddress = $order->getShippingAddress() ?? [];
                $billingAddress = $order->getBillingAddress() ?? [];
                $payload = $affirm->payloadBuild(order: $order, cart: $order->getCart(), shippingAddress: $shippingAddress, billingAddress: $billingAddress, formAction: $formAction);   
            }
        }

        return $this->json([
            'success' => true,
            'payload' => $payload,
        ]);
    }

    #[Route('/affirm/confirm', name: 'affirm_confirm', methods: ['POST'])]
    public function confirm(
        Request $request,
        EntityManagerInterface $em,
        CartManagerService $cartManager,
        OrderService $orderService,
        Gateway $gateway,
        SessionInterface $session,
        CogsHandlerService $cogs,
        OrderLogger $logger, 
        VichS3Helper $s3
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $checkoutToken = $data['checkout_token'] ?? null;
        $orderDetails = $data['order_details'] ?? null;
        $metadata = $orderDetails['metadata'] ?? [];

        $formAction = $metadata['form_action'] ?? null;
        $transactionId = $metadata['transaction_id'] ?? null;
        $orderId = $metadata['order_id'] ?? null;

        if (!$checkoutToken || !$orderDetails) {
            return $this->json(['success' => false, 'message' => 'Missing required fields.'], 400);
        }
        
        switch ($formAction) {
            case 'checkout':
                return $this->handleCheckoutFlow($orderDetails, $checkoutToken, $cartManager, $orderService, $gateway, $session);

            case 'approve_proof':
                return $this->handleApproveProofFlow($em, $orderId, $checkoutToken, $gateway, $logger, $s3, $cogs);

            case 'payment_link':
                return $this->handlePaymentLinkFlow($em, $transactionId, $checkoutToken, $gateway, $orderService, $cogs);

            default:
                return $this->json(['success' => false, 'message' => 'Invalid form action.'], 400);
        }
    }

    #[Route('/affirm/cancel', name: 'affirm_cancel', methods: ['GET', 'POST'])]
    public function cancel(): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => 'Affirm checkout was cancelled by the user.',
        ]);
    }

    private function handleCheckoutFlow($orderDetails, $checkoutToken, $cartManager, $orderService, $gateway, $session): JsonResponse
    {
        $shipping = $this->makeAddressFromAffirm($orderDetails['shipping'] ?? []);
        $billing = $this->makeAddressFromAffirm($orderDetails['billing'] ?? []);

        $cart = $cartManager->getCart();
        $order = $orderService->startOrder($cart, $this->store);
        $order->setShippingAddress($shipping);
        $order->setBillingAddress($billing);
        $order->setPaymentMethod(PaymentMethodEnum::AFFIRM);
        $order->setAgreeTerms(true);
        $order->setTextUpdates(true);
        $order->setTextUpdatesNumber($billing['phone'] ?? '');
        $orderService->setItems($cart->getCartItems());
        $order = $orderService->endOrder();

        $gateway->initialize(PaymentMethodEnum::AFFIRM, 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $gateway->setPaymentData(['checkout_token' => $checkoutToken]);

        $payment = $gateway->startPayment()->execute();
        if ($payment['success']) {
            $cartManager->issueNewCart();
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

        return $this->json(['success' => false, 'message' => 'Payment failed.'], 500);
    }

    private function handleApproveProofFlow($em, $orderId, $checkoutToken, $gateway, $logger, $s3, $cogs): JsonResponse
    {
        $order = $em->getRepository(Order::class)->findOneBy(['orderId' => $orderId, 'store' => $this->getStore()->id]);
        if (!$order instanceof Order) {
            return $this->json(['success' => false, 'message' => 'Order not found.'], 404);
        }


        $order->setPaymentMethod(PaymentMethodEnum::AFFIRM);
        $gateway->initialize(PaymentMethodEnum::AFFIRM, 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $gateway->setActionOnSuccess('APPROVE_PROOF');
        $gateway->setPaymentData(['checkout_token' => $checkoutToken]);

        if ($order->getTotalReceivedAmount() >= 0) {
            $amount = round($order->getTotalAmount() + $order->getRefundedAmount() - $order->getTotalReceivedAmount(), 2);
            $gateway->setCustomAmount($amount);
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

        return $this->json(['success' => false, 'message' => 'Payment failed.'], 500);
    }
    
    private function handlePaymentLinkFlow($em, $transactionId, $checkoutToken, $gateway , $orderService, $cogs): JsonResponse
    {
        $orderTransaction = $em->getRepository(OrderTransaction::class)->findOneBy(['transactionId' => $transactionId]);
        $order = $orderTransaction?->getOrder();

        if (!$orderTransaction || !$order) {
            return $this->json(['success' => false, 'message' => 'Invalid transaction.'], 404);
        }

        $gateway->initialize(PaymentMethodEnum::AFFIRM, 'USD');
        $gateway->setOrder($order);
        $gateway->setTransaction($orderTransaction);
        $gateway->setStore($this->store);
        $gateway->setCustomAmount($orderTransaction->getAmount());
        $gateway->setActionOnSuccess('REDIRECT_ON_PAYMENT_LINK');
        $gateway->setPaymentData(['checkout_token' => $checkoutToken]);

        $payment = $gateway->startPayment()->execute();

        if ($payment['success']) {
            $cogs->syncPaymentLinkAmount($order->getStore(), $order->getOrderAt());

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

        return $this->json(['success' => false, 'message' => 'Payment failed.'], 500);
    }

    private function makeAddressFromCheckout(array $addressData): array
    {
        return [
            'firstName'    => $addressData['firstName'] ?? '',
            'lastName'     => $addressData['lastName'] ?? '',
            'addressLine1' => $addressData['addressLine1'] ?? '',
            'addressLine2' => $addressData['addressLine2'] ?? '',
            'city'         => $addressData['city'] ?? '',
            'state'        => $addressData['state'] ?? '',
            'country'      => $addressData['country'] ?? '',
            'zipcode'      => $addressData['zipcode'] ?? '',
            'email'        => $addressData['email'] ?? '',
            'phone'        => $addressData['phone'] ?? '',
        ];
    }

    private function makeAddressFromAffirm(array $affirmAddress): array
    {
        return [
            'firstName'    => $affirmAddress['name']['first'] ?? '',
            'lastName'     => $affirmAddress['name']['last'] ?? '',
            'addressLine1' => $affirmAddress['address']['line1'] ?? '',
            'addressLine2' => $affirmAddress['address']['line2'] ?? '',
            'city'         => $affirmAddress['address']['city'] ?? '',
            'state'        => $affirmAddress['address']['state'] ?? '',
            'country'      => $affirmAddress['address']['country'] ?? '',
            'zipcode'      => $affirmAddress['address']['zipcode'] ?? '',
            'email'        => $affirmAddress['email'] ?? '',
            'phone'        => $affirmAddress['phone_number'] ?? '',
        ];
    }

    private function proofApprovedLog(Order $order, OrderMessage $approvedProof, OrderLogger $orderLogger, VichS3Helper $s3Helper): void
    {
        $orderLogger->setOrder($order);
        try {
            $image = '';
            $pdf = '';
            $user = $order->getUser();
            $userName = $user->getName() .' <small>('.$user->getUsername().')</small>';

            foreach ($approvedProof->getFiles() as $file) {
                if ($file->getType() == 'PROOF_IMAGE') {
                    $image = '<a href="' . $s3Helper->asset($file, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof Image</a>';
                } elseif ($file->getType() == 'PROOF_FILE') {
                    $pdf = '<a href="' . $s3Helper->asset($file, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof File</a>';
                }
            }
            $content = '<b>Proof Approved By:</b> ' . $userName . '
                        <br/>
                        <b>Payment Method:</b> ' . $order->getPaymentMethod() . '
                        <br/>
                        <b>Proof Approved At:</b> ' . (new \DateTimeImmutable())->format('M d, Y h:i:s A') . '
                        <br/>
                        <b>Comments:</b> ' . $approvedProof->getContent() . '
                        <br/>
                        ' . $image .' | '. $pdf . '';

            $orderLogger->log($content);
        } catch (\Exception $e) {
            $orderLogger->log($e->getMessage());
        }
    }
}
