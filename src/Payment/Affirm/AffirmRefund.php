<?php

namespace App\Payment\Affirm;

use App\Entity\OrderTransaction;

class AffirmRefund extends Base
{
    public function refund(OrderTransaction $transaction, float $amount = 0): array
    {
        $total = (float) $transaction->getAmount();
        $refunded = (float) $transaction->getRefundedAmount();
        $remaining = round($total - $refunded, 2);

        if ($amount == 0) {
            $amount = $remaining;
        }

        if ($amount <= 0 || $amount > $remaining) {
            return [
                'success' => false,
                'message' => 'Invalid refund amount. Available: ' . $remaining,
                'availableAmount' => $remaining
            ];
        }

        $payload = [
            'amount' => (int)(round($amount, 2) * 100),
            'order_id' => $transaction->getOrder()?->getOrderId(),
        ];

        $response = $this->createRefund($transaction->getGatewayId(), $payload);

        if (isset($response['id']) && ($response['type'] ?? null) === 'refund') {
            return [
                'success' => true,
                'status' => 200,
                'message' => 'Refund processed successfully',
                'totalRefundedAmount' => $amount,
                'refundId' => $response['id'], 
                'transactionId' => $transaction->getGatewayId(),
                'metaData' => [
                    'created' => $response['created'] ?? null, 
                    'currency' => $response['currency'] ?? null, 
                    'feeRefunded' => $response['fee_refunded'] ?? 0, 
                    'rawResponse' => $response 
                ]
            ];
        }

        return [
            'success' => false,
            'status' => $response['status_code'] ?? 400,
            'message' => $response['message'] ?? 'Refund processing failed',
            'details' => $response['error'] ?? ($response['type'] ?? null),
        ];
    }

    private function createRefund(string $transactionId, array $payload): array
    {
        $response = $this->sendRequest('POST', "transactions/{$transactionId}/refund", $payload);

        if (!$response['success']) {
            return $response['response'] ?? [
                'message' => 'Refund request failed',
                'status_code' => $response['status'] ?? 500,
                'error' => $response['error'] ?? 'Unknown error'
            ];
        }

        return $response['response'];
    }
}