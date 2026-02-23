<?php

namespace App\Payment\AmazonPay;

use App\Entity\OrderTransaction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AmazonPay extends Base
{
    private CheckoutSession $checkoutSession;
    private Charge $charge;
    private ?string $actionOnSuccess = null;

    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        parent::__construct($params, $kernel, $requestStack);
        $this->checkoutSession = new CheckoutSession($params, $kernel, $urlGenerator, $requestStack);
        $this->charge = new Charge($params, $kernel, $requestStack);
    }

    public function getSignature(): array
    {
        return $this->checkoutSession->getSignature();
    }

    public function getSession(string $sessionId): array
    {
        return $this->checkoutSession->getSession($sessionId);
    }

    public function updateCharge(string $sessionId, float $amount, string $currency, ?string $orderId = null, ?string $path = null): array
    {
        return $this->checkoutSession->updateSession($sessionId, $amount, $currency, $orderId , $path);
    }

    public function completeCheckoutSession(string $sessionId, float $amount, string $currency): array
    {
        return $this->checkoutSession->completeSession($sessionId, $amount, $currency);
    }

    public function getCharge(string $chargeId): array
    {
        return $this->charge->getCharge($chargeId);
    }

    public function createCharge(array $payload, string $checkoutSessionId): array
    {
        return $this->charge->createCharge($payload, $checkoutSessionId);
    }

    public function charge($order, float $amount, string $orderId = '0',?string $path = null): array
    {
        $amount = $amount ? $amount : $order->getTotalAmount();
        $sessionId = $this->paymentData['amazonPaySessionId'] ?? null;

        if (!$sessionId) {
            return [
                'status' => 400,
                'success' => false,
                'message' => 'Amazon Pay Session ID is missing.',
                'action' => 'failed',
            ];
        }

        $currency = $this->currencyCode ?? 'USD';
        $updated = $this->checkoutSession->updateSession($sessionId, $amount, $currency, $orderId, $path );

        if (!isset($updated['status']) || $updated['status'] !== 200) {
            $completed = $this->checkoutSession->completeSession($sessionId, $amount, $currency,);
            if (!isset($completed['status']) || $completed['status'] !== 200) {
                return [
                    'status' => 500,
                    'success' => false,
                    'message' => 'Amazon Pay checkout completion failed.',
                    'action' => 'failed',
                ];
            }

            $session = $this->checkoutSession->getSession($sessionId);
            $sessionData = json_decode($session['response'] ?? '{}', true);

            if (!empty($sessionData['chargeId'])) {
                $chargeDetails = $this->charge->getCharge($sessionData['chargeId']);

                return [
                    'status' => 200,
                    'success' => true,
                    'action' => 'completed',
                    'message' => 'Charge completed via fallback.',
                    'transaction' => [
                        'gatewayId' => $sessionData['chargeId'],
                    ],
                    'charge' => $chargeDetails,
                ];
            }

            return [
                'status' => 200,
                'success' => true,
                'action' => 'completed',
                'message' => 'Session completed, charge ID unavailable.',
            ];
        }

        return [
            'status' => 200,
            'success' => true,
            'action' => 'completed',
            'message' => 'Charge updated successfully.',
            'transaction' => [
                'gatewayId' => $updated['chargeId'] ?? null,
            ],
            'data' => $updated,
        ];
    }

    public function handleSessionAndCharge(string $sessionId, float $amount, string $currency = 'USD', ?string $orderId = null, ?string $path = null): array
    {
        $updatedAmazonData = null;
        $sessionData = $this->getSession($sessionId);

        if (!isset($sessionData['status']) || $sessionData['status'] !== 200) {
            return [
                'success' => false,
                'status' => $sessionData['status'] ?? 500,
                'message' => 'Invalid Amazon Checkout Session',
            ];
        }

        $chargeData = $this->updateCharge($sessionId, round($amount, 2), $currency, $orderId, $path);
        $chargeDataDecoded = json_decode($chargeData['response'] ?? '{}', true);

        if (!empty($chargeDataDecoded['webCheckoutDetails']['amazonPayRedirectUrl'])) {
            $updatedAmazonData = $chargeDataDecoded;
        }

        return [
            'success' => (bool) $updatedAmazonData,
            'status' => 200,
            'data' => $updatedAmazonData,
        ];
    }
    
    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }
}
