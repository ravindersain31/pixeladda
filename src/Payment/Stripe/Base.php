<?php

namespace App\Payment\Stripe;

use App\Payment\AbstractPayment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Base extends AbstractPayment
{
    public const NAME = 'stripe';

    protected $apiKey;
    protected string $secretKey;
    protected $publicKey;
    protected $stripeEnv = 'test';
    protected array $redirectUrls = [];

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        $this->secretKey = $this->parameterBag->get('STRIPE_SECRET_KEY');
        $this->publicKey = $this->parameterBag->get('STRIPE_PUB_KEY');
        $this->stripeEnv = $this->parameterBag->get('STRIPE_ENV');

        $this->setDefaultRedirectUrls();
    }

    private function setDefaultRedirectUrls(): void
    {
        $this->redirectUrls = [
            'return_url' => $this->urlGenerator->generate('stripe_return', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('stripe_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function getReturnUrl(): string
    {
        return $this->redirectUrls['return_url'];
    }

    public function getCancelUrl(): string
    {
        return $this->redirectUrls['cancel_url'];
    }

    public function setRedirectUrls(string $returnUrl, string $cancelUrl): void
    {
        $this->redirectUrls['return_url'] = $returnUrl;
        $this->redirectUrls['cancel_url'] = $cancelUrl;
    }
}
