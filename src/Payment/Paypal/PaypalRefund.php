<?php

namespace App\Payment\Paypal;

use App\Entity\OrderTransaction;

class PaypalRefund extends Base
{
    public function refund(OrderTransaction $transaction, float $amount = 0): array
    {
        $this->getToken();
        $currency = $transaction->getCurrency();
        $captures = $transaction->getMetaDataKey('captures') ?? [];

        try {
            $totalRefundedAmount = 0;
            if ($amount > 0) {
                // if we need to issue the partial refund;
                $captureIdToBeRefunded = null;
                foreach ($captures as $capture) {
                    $balanceAmount = round(floatval($capture['amount'] - $capture['refunded']), 2);
                    if ($balanceAmount >= $amount) {
                        $captureIdToBeRefunded = $capture['id'];
                        break;
                    }
                }
                if ($captureIdToBeRefunded) {
                    $response = $this->process($captureIdToBeRefunded, $amount, $currency);
                    if (isset($response['success']) && $response['success']) {
                        $captures[$captureIdToBeRefunded]['refunded'] += floatval($amount);
                        if (!isset($captures[$captureIdToBeRefunded]['refunds'])) {
                            $captures[$captureIdToBeRefunded]['refunds'] = [];
                        }
                        $captures[$captureIdToBeRefunded]['refunds'][] = $response['refundId'];
                        $totalRefundedAmount = floatval($amount);
                    }
                }
            } else {
                foreach ($captures as $capture) {
                    $balanceAmount = round(floatval($capture['amount'] - $capture['refunded']), 2);
                    $response = $this->process($capture['id'], $balanceAmount, $currency);
                    if (isset($response['success']) && $response['success']) {
                        $captures[$capture['id']]['refunded'] += floatval($amount);
                        if (!isset($captures[$capture['id']]['refunds'])) {
                            $captures[$capture['id']]['refunds'] = [];
                        }
                        $captures[$capture['id']]['refunds'][] = $response['refundId'];
                        $totalRefundedAmount = $balanceAmount;
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Payment refunded successfully.',
                'metaData' => ['captures' => $captures],
                'totalRefundedAmount' => $totalRefundedAmount,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
    }

    private function process(string $captureId, float $amount, string $currency = 'USD'): array
    {
        $response = $this->client->request('POST', '/v2/payments/captures/' . $captureId . '/refund', [
            'base_uri' => $this->apiUrl[$this->env],
            'auth_bearer' => $this->accessToken,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'amount' => [
                    'value' => $amount,
                    'currency_code' => $currency,
                ]
            ]),
        ]);
        $content = json_decode($response->getContent(false), true);
        if (isset($content['status']) && $content['status'] === 'COMPLETED') {
            return [
                'success' => true,
                'message' => 'Refunded successfully',
                'refundId' => $content['id'],
            ];
        }
        $message = $content['message'] ?? 'There was some issues in processing your refund request.';
        throw new \Exception('Paypal: ' . $message);
    }
}