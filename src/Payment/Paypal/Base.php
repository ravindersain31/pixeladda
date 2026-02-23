<?php

namespace App\Payment\Paypal;

use App\Payment\AbstractPayment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Base extends AbstractPayment
{
    private string $clientId;
    private string $clientSecret;
    protected array $apiUrl = [
        'sandbox' => 'https://api-m.sandbox.paypal.com',
        'live' => 'https://api.paypal.com',
    ];

    protected array $redirectUrls = [];

    protected ?string $accessToken = null;

    public function __construct(
        protected readonly HttpClientInterface   $client,
        protected readonly ParameterBagInterface $parameterBag,
        protected readonly UrlGeneratorInterface $urlGenerator,
    )
    {
        $this->env = $this->parameterBag->get('PAYPAL_ENV');
        $this->clientId = $this->parameterBag->get('PAYPAL_CLIENT_ID');
        $this->clientSecret = $this->parameterBag->get('PAYPAL_CLIENT_SECRET');

        $this->redirectUrls['return_url'] = $this->urlGenerator->generate('paypal_return', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->redirectUrls['cancel_url'] = $this->urlGenerator->generate('paypal_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getToken(): array
    {
        $response = $this->client->request('POST', '/v1/oauth2/token', [
            'base_uri' => $this->apiUrl[$this->env],
            'auth_basic' => [
                $this->clientId,
                $this->clientSecret,
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
                'ignoreCache' => 'true',
            ],
        ]);

        $this->accessToken = $response->toArray()['access_token'];
        return json_decode($response->getContent(), true);
    }

    public function setRedirectUrls(string $returnUrl, string $cancelUrl): void
    {
        $this->redirectUrls['return_url'] = $returnUrl;
        $this->redirectUrls['cancel_url'] = $cancelUrl;
    }

    protected function paymentSource(string $source): array
    {
        return match ($source) {
            'paypal' => [
                $source => [
                    'experience_context' => [
                        'return_url' => $this->redirectUrls['return_url'],
                        'cancel_url' => $this->redirectUrls['cancel_url'],
                    ]
                ]
            ]
        };
    }
}