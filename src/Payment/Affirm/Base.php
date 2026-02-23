<?php

namespace App\Payment\Affirm;

use App\Payment\AbstractPayment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Base extends AbstractPayment
{
    protected string $privateApiKey;
    protected string $publicApiKey;
    protected string $baseUrl;
    protected string $apiVersion = 'v1';
    protected string $env;

    public function __construct(ParameterBagInterface $params)
    {
        $this->privateApiKey = $params->get('AFFIRM_PRIVATE_API_KEY');
        $this->publicApiKey = $params->get('AFFIRM_PUBLIC_API_KEY');
        $this->env = $params->get('AFFIRM_ENV');

        $this->baseUrl = $this->env === 'sandbox'
            ? "https://sandbox.affirm.com/api/{$this->apiVersion}"
            : "https://api.affirm.com/api/{$this->apiVersion}";
    }

    protected function sendRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->publicApiKey . ':' . $this->privateApiKey),
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $decoded = json_decode($response, true) ?: $response;

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'status' => $httpCode,
                'response' => $decoded,
                'error' => $decoded['message'] ?? ($decoded['error'] ?? 'Unknown error')
            ];
        }

        return ['success' => true, 'status' => $httpCode, 'response' => $decoded];
    }
}
