<?php

namespace App\Payment\AmazonPay;

use App\Entity\OrderTransaction;

class AmazonPayRefund extends Base
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
                'message' => 'Invalid refund amount.',
            ];
        }

        $payload = [
            'chargeId' => $transaction->getGatewayId(),
            'refundAmount' => [
                'amount' => round($amount, 2),
                'currencyCode' => $transaction->getCurrency()?->getCode() ?? 'USD',
            ],
        ];
        
        $response = $this->createRefund($payload);

        $refundStatus = $response['statusDetails']['state'] ?? null;

        if ($refundStatus === 'RefundInitiated' || $refundStatus === 'Completed') {
            return [
                'success' => true,
                'totalRefundedAmount' => $amount,
                'metaData' => [
                    'refundId' => $response['refundId'] ?? null,
                    'amazonResponse' => $response
                ]
            ];
        }

        return [
            'success' => false,
            'message' => $response['statusDetails']['reasonCode'] ?? 'Refund failed',
        ];
    }

    private function createRefund(array $payload): array
    {
        $headers = array('x-amz-pay-idempotency-key' => uniqid());

        $result = $this->client->createRefund($payload, $headers);
        if (isset($result['response'])) {
            return json_decode($result['response'], true);
        }

        return [];
    }
}
