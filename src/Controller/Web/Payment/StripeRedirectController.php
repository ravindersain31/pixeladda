<?php

namespace App\Controller\Web\Payment;

use Stripe\Webhook;
use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Service\OrderLogger;
use Psr\Log\LoggerInterface;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderProofApprovedEvent;
use App\Event\OrderReceivedEvent;
use App\Service\CartManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Payment\Stripe\Stripe;

class StripeRedirectController extends AbstractController
{
    private const STRIPE_WEBHOOK_SECRET = 'whsec_IKKn4HDaQglxAzei30hZ4KVarHgTMJIu';

    protected string $eventType;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CartManagerService $cartManagerService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLogger $orderLogger,
        private readonly LockFactory $lockFactory,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    #[Route('/checkout/cancel/{orderId}', name: 'stripe_cancel', defaults: ['orderId' => 0])]
    public function checkoutCancel($orderId, Request $request): Response
    {
        $order = $this->getOrder($orderId);
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $this->orderLogger->setOrder($order);
        $this->logOrderCancellation($order);

        $actionOnSuccess = $request->get('actionOnSuccess');

        $gatewayId = $order->getTransactions()->last()?->getGatewayId();
        $transaction = $this->entityManager->getRepository(OrderTransaction::class)->findOneBy(['gatewayId' => $gatewayId]);

        if($transaction instanceof OrderTransaction) {
            $transaction->setStatus(PaymentStatusEnum::CANCELLED);
            $transaction->setMetaDataKey('cancelledAt', new \DateTimeImmutable());
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
        }

        if ($actionOnSuccess) {
            if (strtoupper($actionOnSuccess) === 'REDIRECT_ON_PAYMENT_LINK') {
                return $this->redirectToRoute('payment_link', ['requestId' => $transaction->getTransactionId()]);
            } else if (strtoupper($actionOnSuccess) === 'APPROVE_PROOF') {
                return $this->redirectToRoute('order_proof_approve', ['oid' => $orderId]);
            }
        }


        $cartId = $order->getCart()->getCartId();
        if (!$cartId) {
            return $this->redirectToRoute('cart');
        }

        $this->addFlash('danger', 'Checkout cancelled');

        return $this->redirectToRoute('checkout', ['id' => $cartId]);
    }

    #[Route('/checkout/success/{orderId}', name: 'stripe_return', defaults: ['orderId' => 0])]
    public function checkoutSuccess($orderId, Request $request, UrlGeneratorInterface $urlGenerator): Response
    {
        $order = $this->getOrder($orderId);
        $actionOnSuccess = $request->get('actionOnSuccess');

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $this->orderLogger->setOrder($order);
        $this->logOrderSuccess($order);

        $gatewayId = $order->getTransactions()->last()?->getGatewayId();
        $transaction = $this->entityManager->getRepository(OrderTransaction::class)->findOneBy(['gatewayId' => $gatewayId]);

        $response = [
            'message' => 'Thank you for your payment. We will begin processing your order immediately. <br/>Thank you for choosing Yard Sign Plus.',
        ];
        if ($actionOnSuccess) {
            if (strtoupper($actionOnSuccess) === 'APPROVE_PROOF') {
                $approvedProof = $this->entityManager->getRepository(OrderMessage::class)->getLastProofMessage($order);
                $this->eventDispatcher->dispatch(new OrderProofApprovedEvent($order, $approvedProof), OrderProofApprovedEvent::NAME);
                $order->setApprovedProof($approvedProof);
                $order->setProofApprovedAt(new \DateTimeImmutable());
                $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                $order->setIsApproved(true);

                $this->entityManager->persist($order);
                $this->entityManager->flush();

                
                $response['message'] = 'Thank you for approving your proof and completing payment. We will begin processing your order immediately. <br/>Thank you for choosing Yard Sign Plus.';
            } else if (strtoupper($actionOnSuccess) === 'REDIRECT_ON_PAYMENT_LINK') {
                $response['message'] = 'Redirecting to payment link...';
                $transaction->setStatus(PaymentStatusEnum::COMPLETED);
                $this->entityManager->persist($transaction);
                $this->entityManager->flush();
                return $this->redirectToRoute('payment_link', ['requestId' => $transaction->getTransactionId()]);
            }
        } else {
            $this->cartManagerService->issueNewCart();
        }
        $this->addFlash('success', $response['message']);

        return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
    }

    #[Route('/stripe/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request, Stripe $stripe): JsonResponse
    {
        $amount = $request->get('amount');
        /**
         * @var \Stripe\PaymentIntent $intent
         */
        $intent = $stripe->createPaymentIntent($amount);

        return new JsonResponse($intent);
    }

    #[Route('/stripe/payment-intent/verify', name: 'stripe_verify_payment_intent', methods: ['GET'])]
    public function paymentIntentVerify(Request $request, Stripe $stripe): JsonResponse
    {
        $intent = $stripe->verifyPaymentIntent('pi_3QJM1JR7bSdMBTR21oieQkzu');

        return new JsonResponse($intent);
    }

    #[Route('/stripe/webhook/hook', name: 'stripe_webhook', methods: ['POST'], priority: 1)]
    public function stripeWebhook(Request $request): JsonResponse
    {
        return new JsonResponse(['status' => 'success']);
        $lock = $this->lockFactory->createLock('stripe_webhook_lock', 1.69);
        $lock->acquire(true);

        $event = $this->constructStripeEvent($request);
        if (!$event) {
            return new JsonResponse(['error' => 'Invalid Stripe event'], Response::HTTP_BAD_REQUEST);
        }

        $this->handleStripeEvent($event);
        $lock->release();
        return new JsonResponse(['status' => 'success']);
    }

    private function getCartId($cartId): ?string
    {
        return $cartId ?? $this->cartManagerService->getCart()->getCartId();
    }

    private function getOrder($orderId): ?Order
    {
        return $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);
    }

    private function getTransaction($transactionId): ?OrderTransaction
    {
        return $this->entityManager->getRepository(OrderTransaction::class)->findOneBy([
            'gatewayId' => $transactionId,
        ]);
    }

    private function constructStripeEvent(Request $request): ?object
    {
        $payload = $request->getContent();
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $webHookSecret = self::STRIPE_WEBHOOK_SECRET;
        try {
            return Webhook::constructEvent($payload, $sigHeader, $webHookSecret);
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException) {
            return null;
        }
    }

    private function handleStripeEvent($event): void
    {
        $type = $event->type;
        $this->eventType = $type;
        $object = $event->data->object;

        switch ($type) {
            case 'checkout.session.completed':
            case 'payment_intent.succeeded':
            case 'charge.succeeded':
            case 'charge.updated':
            case 'charge.failed':
            case 'payment_intent.payment_failed':
            case 'payment_intent.canceled':
                $this->updateOrder($object);
                break;
            case 'refund.created':
            case 'charge.refunded':
            case 'refund.updated':
            case 'refund.failed':
                $this->logRefund($object);
                break;
            default:
                $this->logger->warning('Unhandled Stripe event type', ['event_type' => $type]);
                break;
        }
    }

    private function updateOrder($object): void
    {
        $order = $this->getOrder($object->metadata->orderId);
        if (!$order) {
            return;
        }

        $this->orderLogger->setOrder($order);
        $transaction = $order->getTransactions()->last();
        $transactionId = $transaction ? $transaction->getTransactionId() : 'N/A';

        if ($object->captured ?? false) {
            $this->processCapturedPayment($order, $transaction, $object);
        } elseif (isset($object->captured) && !$object->captured && $order->getPaymentStatus() !== PaymentStatusEnum::COMPLETED) {
            $this->processPendingPayment($order, $transaction, $object);
        }

        $receiptUrl = $object->receipt_url ? sprintf('<a href="%s" target="_blank">Receipt URL </a>', $object->receipt_url) : 'No Receipt URL';

        $this->orderLogger->log(sprintf(
            'Stripe Webhook processed:- Event Type: %s <br> Payment Intent ID: %s <br> Stripe Payment Status: <b>%s</b> <br> %s',
            $this->eventType,
            $object->payment_intent,
            $object->status,
            $receiptUrl
        ));
    }

    private function processCapturedPayment(Order $order, $transaction, $object): void
    {
        if ($order->getPaymentStatus() === PaymentStatusEnum::COMPLETED) {
            return;
        }

        $order->setPaymentStatus(PaymentStatusEnum::COMPLETED)
            ->setStatus(OrderStatusEnum::RECEIVED)
            ->setTotalReceivedAmount($this->convertCentsToDollars($object->amount_captured));

        $transaction->setStatus(PaymentStatusEnum::COMPLETED)
            ->setMetaDataKey('stripeId', $object->id)
            ->setMetaDataKey('paymentIntentId', $object->payment_intent)
            ->setGatewayId($object->payment_intent);

        $this->orderLogger->log(sprintf(
            'Transaction Id %s status has been updated to %s',
            $transaction->getTransactionId(),
            $transaction->getStatus()
        ));

        $this->entityManager->persist($transaction);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);
    }

    private function processPendingPayment(Order $order, $transaction, $object): void
    {
        if ($order->getPaymentStatus() === PaymentStatusEnum::COMPLETED) {
            return;
        }

        $order->setPaymentStatus(PaymentStatusEnum::PENDING);
        $transaction->setStatus(PaymentStatusEnum::PENDING)
            ->setMetaDataKey('stripeId', $object->id)
            ->setMetaDataKey('paymentIntentId', $object->payment_intent);

        $this->orderLogger->log(sprintf(
            'Transaction Id %s status has been updated to %s',
            $transaction->getTransactionId(),
            $transaction->getStatus()
        ));

        $this->entityManager->persist($transaction);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    private function logOrderCancellation(Order $order): void
    {
        $transactionId = $order->getTransactions()->last()?->getTransactionId() ?? 'N/A';
        $this->orderLogger->log(sprintf(
            'Checkout cancelled: Order ID %s, Transaction ID: %s',
            $order->getOrderId(),
            $transactionId
        ));
    }

    private function logOrderSuccess(Order $order): void
    {
        $transactionId = $order->getTransactions()->last()?->getTransactionId() ?? 'N/A';
        $this->orderLogger->log(sprintf(
            'Checkout successful: Order ID %s, Transaction ID: %s',
            $order->getOrderId(),
            $transactionId
        ));
    }

    private function logRefund($object): void
    {
        // Retrieve the transaction associated with the refund
        $transaction = $this->getTransaction($object->payment_intent);
        $order = $transaction->getOrder();
        if (!$transaction) {
            return;
        }

        $this->orderLogger->setOrder($order);

        $totalRefundedAmount = null;
        $refundedAmount = null;
        if ($this->eventType === 'charge.refunded' && $object->amount_refunded) {
            $totalRefundedAmount = '| Total Refunded Amount: $' . number_format($this->convertCentsToDollars($object->amount_refunded ?? 0), 2, '.', ',');
        } else {
            $refundedAmount = '| Amount Refunded: $' . number_format($this->convertCentsToDollars($object->amount ?? 0), 2, '.', ',');
        }

        // Retrieve the last transaction for this order
        $transactionId = $transaction ? $transaction->getTransactionId() : 'N/A';

        // Log refund details with event type and status
        $this->orderLogger->log(sprintf(
            '
                Refund Event: %s | Order ID: %s | Transaction ID: %s,
                <br>Refund ID: %s %s %s,
                <br>Refund Status: %s,
                <br>Event Status: %s,
                <br>It may take a few days for the money to reach the customer\'s bank account.
            ',
            $this->eventType, // The event type (e.g., 'refund.created')
            $order->getOrderId(),
            $transactionId,
            $object->id ?? 'N/A',
            $refundedAmount,
            $totalRefundedAmount,
            $object->status ?? 'unknown',
            $object->reason ?? 'N/A'
        ));
    }


    private function convertCentsToDollars(int $cents): float
    {
        return $cents / 100;
    }
}
