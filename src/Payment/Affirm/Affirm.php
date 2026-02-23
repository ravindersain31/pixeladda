<?php

namespace App\Payment\Affirm;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderTransaction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Affirm extends Base
{
    private PayloadBuilder $payloadBuilder;
    private Transactions $transactions;
    private ?string $actionOnSuccess = null;

   public function __construct(ParameterBagInterface $params, UrlGeneratorInterface $urlGenerator)
   {
       parent::__construct($params);
       $this->payloadBuilder = new PayloadBuilder($params, $urlGenerator);
       $this->transactions = new Transactions($params);
   }

    public function payloadBuild(?Order $order = null, ?Cart $cart = null, array $shippingAddress = [], array $billingAddress = [], string $formAction = '', ?OrderTransaction $orderTransaction = null): array
    {
        return $this->payloadBuilder->buildPayload($order, $cart, $shippingAddress, $billingAddress, $formAction, $orderTransaction);
    }

    public function charge(Order $order, float $amount): array
    {
        return $this->processPayment($order, $amount);
    }

    public function processPayment(Order $order, float $amount): array
    {
        $checkoutToken = $this->paymentData['checkout_token'] ?? null;

        if (!$checkoutToken) {
            return [
                'status' => 400,
                'success' => false,
                'message' => 'Missing checkout token',
                'action' => 'failed',
                'transaction' => null,
                'data' => null
            ];
        }

        $authorizeResult = $this->transactions->authorize($order, $checkoutToken);

        if (!$authorizeResult['success']) {
            return [
                'status' => $authorizeResult['status'] ?? 400,
                'success' => false,
                'action' => 'failed',
                'message' => $authorizeResult['message'] ?? 'Authorization failed',
                'transaction' => $authorizeResult['transaction'] ?? null,
                'data' => [
                    'authorize' => $authorizeResult
                ]
            ];
        }

        $transactionId = $authorizeResult['data']['id'] ?? null; 
        if (!$transactionId) {
            return [
                'status' => 500,
                'success' => false,
                'action' => 'failed',
                'message' => 'Transaction ID missing after authorization',
                'transaction' => null,
                'data' => [
                    'authorize' => $authorizeResult
                ]
            ];
        }

        $captureResult = $this->transactions->capture($transactionId);

        if (!$captureResult['success']) {
            return [
                'status' => $captureResult['status'] ?? 400,
                'success' => false,
                'action' => 'failed',
                'message' => $captureResult['message'] ?? 'Capture failed',
                'transaction' => [
                    'gatewayId' => $transactionId,
                    'status' => 'authorized'
                ],
                'data' => [
                    'authorize' => $authorizeResult,
                    'capture' => $captureResult
                ]
            ];
        }

        return [
            'status' => 200,
            'success' => true,
            'action' => 'completed',
            'message' => 'Payment processed successfully',
            'transaction' => [
                'gatewayId' => $transactionId, 
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
            ],
            'data' => [
                'authorize' => $authorizeResult,
                'capture' => $captureResult
            ]
        ];
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }
}
