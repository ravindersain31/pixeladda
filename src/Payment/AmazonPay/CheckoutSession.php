<?php

namespace App\Payment\AmazonPay;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutSession extends Base
{
    public function __construct(
        private ParameterBagInterface $params,
        private KernelInterface $kernel,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack
    ) {
        parent::__construct($params, $kernel, $requestStack); 
    }

    public function getSignature(): array
    {
        $checkoutResultReturnUrl = $this->urlGenerator->generate('amazonpay_result', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $payload = json_encode([
            "webCheckoutDetails" => [
                "checkoutResultReturnUrl" => $checkoutResultReturnUrl,
                "checkoutReviewReturnUrl" => $this->checkoutReviewReturnUrl,
            ],
            "storeId" => $this->storeId,
            "scopes" => ["name", "email", "phoneNumber", "billingAddress"],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $signature = $this->client->generateButtonSignature($payload);

        return [
            'signature' => $signature,
            'payload' => $payload,
            'checkoutResultReturnUrl' => $checkoutResultReturnUrl,
        ];
    }

    public function getSession(string $sessionId): array
    {
        return $this->client->getCheckoutSession($sessionId);
    }

    public function updateSession(string $sessionId, float $amount, string $currency, ?string $orderId, ?string $path = null ): array
    {
        $params = [];
        if (!empty($orderId)) {
            $params['orderId'] = $orderId;
        }
        if (!empty($path)) {
            $params['path'] = ltrim($path, '/');
        }
        $checkoutResultReturnUrl = $this->urlGenerator->generate('amazonpay_result', $params, UrlGeneratorInterface::ABSOLUTE_URL);
        $payload = [
            "webCheckoutDetails" => [
                "checkoutResultReturnUrl" => $checkoutResultReturnUrl
            ],
            "paymentDetails" => [
                "paymentIntent" => "AuthorizeWithCapture",
                "canHandlePendingAuthorization" => false,
                "softDescriptor" => "Descriptor",
                "chargeAmount" => [
                    "amount" => $amount,
                    "currencyCode" => $currency
                ]
            ],
            "merchantMetadata" => [
                "merchantReferenceId" => uniqid(),
                "merchantStoreName" => "Yard Sign Plus",
                "noteToBuyer" => "Thank you for your order",
                "customInformation" => "Additional info"
            ]
        ];

        return $this->client->updateCheckoutSession($sessionId, json_encode($payload));
    }

    public function completeSession(string $sessionId, float $amount, string $currency): array
    {
        $payload = [
            "chargeAmount" => [
                "amount" => $amount,
                "currencyCode" => $currency
            ]
        ];

        return $this->client->completeCheckoutSession($sessionId, json_encode($payload));
    }
}
