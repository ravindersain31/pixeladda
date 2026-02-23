<?php

namespace App\Payment\Braintree;

use App\Entity\OrderTransaction;
use Braintree\Result\Error;
use Braintree\Result\Successful;

class BraintreeRefund extends Base
{
    public function refund(OrderTransaction $transaction, float $amount = 0): array
    {
        if ($amount <= 0) {
            $amount = null;
        }
        $result = $this->gateway->transaction()->refund($transaction->getGatewayId(), $amount);
        $response = $this->handleResult($result);
        if (!$response['success'] && !$response['isSettled'] && $amount === null) {
            $result = $this->gateway->transaction()->void($transaction->getGatewayId());
            $response = $this->handleResult($result);
        }
        return $response;
    }

    private function handleResult(Successful|Error $result): array
    {
        return match (true) {
            $result instanceof Successful => $this->handleSuccess($result),
            $result instanceof Error => $this->handleError($result),
        };
    }

    private function handleSuccess(Successful $result): array
    {
        return [
            'success' => true,
            'message' => 'Payment refunded successfully.',
            'metaData' => [
                'refundId' => $result->transaction->id,
                'paymentReceipt' => $result->transaction->paymentReceipt,
            ],
            'totalRefundedAmount' => $result->transaction->amount
        ];
    }

    private function handleError(Error $result): array
    {
        $isSettled = 'Cannot refund transaction unless it is settled.' !== $result->message;
        return [
            'success' => false,
            'isSettled' => $isSettled,
            'message' => 'Braintree: ' . $result->message,
        ];
    }

}