<?php

namespace App\Payment\AmazonPay;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class Charge extends Base
{
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, RequestStack $requestStack)
    {
        parent::__construct($params, $kernel, $requestStack);
    }

    public function getCharge(string $chargeId): array
    {
        return $this->client->getCharge($chargeId);
    }

    public function createCharge(array $payload, string $checkoutSessionId): array
    {
        return $this->client->createCharge($payload, $checkoutSessionId);
    }
}
