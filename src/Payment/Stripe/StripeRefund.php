<?php

namespace App\Payment\Stripe;

use Stripe\Refund;
use Stripe\StripeClient;
use App\Service\OrderLogger;
use App\Entity\OrderTransaction;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripeRefund extends Base
{
    public function __construct(private readonly ParameterBagInterface $parameterBag, private readonly OrderLogger $orderLogger)
    {
        $this->secretKey = $this->parameterBag->get('STRIPE_SECRET_KEY');
        $this->publicKey = $this->parameterBag->get('STRIPE_PUB_KEY');
        $this->stripeEnv = $this->parameterBag->get('STRIPE_ENV');

        $this->stripeClient = new StripeClient($this->secretKey);
    }

    protected StripeClient $stripeClient;

    public function refund(OrderTransaction $transaction, float $amount = 0): array
    {
        try {
            // Set amount to null if zero or less; otherwise, convert to cents
            $amount = $amount > (float) 0 ? (int) ($amount * 100) : null;

            $transactionId = $transaction->getGatewayId();
            // Initiate refund with specified or full amount

            if($amount === null) {
                $result = $this->stripeClient->refunds->create([
                    'payment_intent' => $transactionId,
                ]);
            }else {
                $result = $this->stripeClient->refunds->create([
                    'payment_intent' => $transactionId,
                    'amount' => $amount,
                ]);
            }

            $response = $this->handleResult($result);

            // If refund failed, retry with no amount to capture full refund if possible
            if (!$response['success'] && !$response['isSettled'] && $amount === null) {
                $result = $this->stripeClient->refunds->create(['charge' => $transactionId]);
                $response = $this->handleResult($result);
            }

            $this->logRefund($transaction, $result, $result->amount ?? $amount);

            return $response;
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'isSettled' => false,
                'message' => 'Stripe API Error: ' . $e->getMessage(),
            ];
        }
    }

    private function handleResult($result): array
    {
        return match (true) {
            isset($result->status) && $result->status === 'succeeded' => $this->handleSuccess($result),
            default => $this->handleError($result),
        };
    }

    private function handleSuccess($result): array
    {
        return [
            'success' => true,
            'message' => 'Payment refunded successfully.',
            'metaData' => [
                'refundId' => $result->id,
                'paymentReceipt' => $result->receipt_url ?? null,
            ],
            'totalRefundedAmount' => $result->amount / 100,
        ];
    }

    private function handleError($result): array
    {
        $isSettled = 'Cannot refund transaction unless it is settled.' !== ($result->message ?? '');
        return [
            'success' => false,
            'isSettled' => $isSettled,
            'message' => 'Stripe: ' . ($result->message ?? 'Unknown error'),
        ];
    }

    private function logRefund($transaction, $result, $amount): void
    {
        // Retrieve the transaction associated with the refund
        $order = $transaction->getOrder();
        if (!$transaction) {
            return;
        }

        $this->orderLogger->setOrder($order);

        $refundedAmount = ($amount / 100) ?? $transaction->getAmount();

        // Retrieve the last transaction for this order
        $transactionId = $transaction ? $transaction->getTransactionId() : 'N/A';

        // Log refund details with event type and status
        $this->orderLogger->log(sprintf(
            '
                Refund Event: - | Order ID: %s | Transaction ID: %s,
                <br>Refund ID: %s | Refund Amount: $%s,
                <br>Refund Status: <b>%s</b>,
                <br>It may take a few days for the money to reach the customer\'s bank account.
            ',
            $order->getOrderId(),
            $transactionId,
            $result->id ?? 'N/A',
            $refundedAmount,
            $result->status ?? 'unknown',
        ));
    }
}
