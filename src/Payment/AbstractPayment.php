<?php

namespace App\Payment;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractPayment
{
    protected string $env = 'sandbox';

    protected string $currencyCode = 'USD';

    protected array $customFields = [];

    protected array $paymentData = [];

    protected ?string $paymentNonce;

    protected ?string $paymentIntent;

    public function setEnv(string $env): void
    {
        $this->env = $env;
    }

    public function setCurrencyCode(string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    public function setCustomFields(array $customFields): void
    {
        $this->customFields = $customFields;
    }

    public function setPaymentNonce(?string $paymentNonce): void
    {
        $this->paymentNonce = $paymentNonce;
    }

    public function setPaymentIntent(?string $paymentIntent): void
    {
        $this->paymentIntent = $paymentIntent;
    }

    protected function throwError(string $message): void
    {
        throw new \Exception($message);
    }

    public function setPaymentData(array $paymentData): void
    {
        $this->paymentData = $paymentData;
    }

}