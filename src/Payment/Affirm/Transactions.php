<?php

namespace App\Payment\Affirm;

use App\Entity\Order;

class Transactions extends Base
{
    public function authorize(Order $order, string $checkoutToken): array
    {
        $payload = [
            'transaction_id' => $checkoutToken,
            'order_id' => $order->getOrderId(),
        ];

        $response = $this->sendRequest('POST', 'transactions', $payload);

        if (!$response['success']) {
            return $this->formatErrorResponse($response, 'Authorization failed');
        }

        return $this->formatSuccessResponse($response, 'authorized');
    }

    public function capture(string $transactionId): array
    {
        $response = $this->sendRequest('POST', "transactions/{$transactionId}/capture");

        if (!$response['success']) {
            return $this->formatErrorResponse($response, 'Capture failed');
        }

        return $this->formatSuccessResponse($response, 'captured');
    }

    public function read(string $transactionId): array
    {
        $response = $this->sendRequest('GET', "transactions/{$transactionId}");

        if (!$response['success']) {
            return $this->formatErrorResponse($response, 'Read transaction failed');
        }

        return $this->formatSuccessResponse($response, 'read');
    }

    private function formatErrorResponse(array $response, string $defaultMessage): array
    {
        return [
            'status' => $response['status'] ?? 500,
            'success' => false,
            'action' => 'failed',
            'message' => $response['error'] ?? $defaultMessage,
            'data' => $response['response'] ?? null,
        ];
    }

    private function formatSuccessResponse(array $response, string $action): array
    {
        $data = $response['response'];

        return [
            'status' => $response['status'],
            'success' => true,
            'action' => $action,
            'message' => ucfirst($action) . ' successful',
            'transaction' => [
                'gatewayId' => $data['id'] ?? null,
                'type' => $data['type'] ?? null,
                'amount' => $data['amount'] ?? null,
            ],
            'data' => $data,
        ];
    }
}