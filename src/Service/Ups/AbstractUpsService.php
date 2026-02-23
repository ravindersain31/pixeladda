<?php
namespace App\Service\Ups;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractUpsService
{
    protected HttpClientInterface $client;
    private string $clientId;
    private string $clientSecret;
    private bool $isSandbox;

    private ?string $cachedToken = null;

    public function __construct(HttpClientInterface $client, string $clientId, string $clientSecret, bool $isSandbox = true)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->isSandbox = $isSandbox;
    }

    protected function isSandBox(): bool
    {
        return $this->isSandbox;
    }

    protected function getBaseUrl(): string
    {
        return $this->isSandbox ? 'https://wwwcie.ups.com' : 'https://onlinetools.ups.com';
    }

    protected function getAccessToken(): string
    {
        if ($this->cachedToken) {
            return $this->cachedToken;
        }

        $response = $this->client->request('POST', $this->getBaseUrl() . '/security/v1/oauth/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $data = $response->toArray();

        return $this->cachedToken = $data['access_token'];
    }

    protected function sendRequest(string $method, string $endpoint, array $payload = []): ResponseInterface
    {
        $url = $this->getBaseUrl() . $endpoint;

        return $this->client->request($method, $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);
    }
}
