<?php

namespace App\Helper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidatorHelper
{
    private string $siteKey;

    private string $secretKey;

    public function __construct(protected readonly HttpClientInterface $client, ParameterBagInterface $parameterBag)
    {
        $this->siteKey = $parameterBag->get('RECAPTCHA_SITE_KEY');
        $this->secretKey = $parameterBag->get('RECAPTCHA_SECRET_KEY');
    }

    public function validate(string $recaptchaResponse): bool
    {
        $url = 'https://recaptchaenterprise.googleapis.com/v1/projects/yardsignplus/assessments?key=' . $this->secretKey;
        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'event' => [
                        'token' => $recaptchaResponse,
                        'siteKey' => $this->siteKey,
                    ],
                ]),
            ]);

            $content = json_decode($response->getContent(), true);
            return $content['tokenProperties']['valid'] ?? false;
        } catch (ClientException $exception) {
            return false;
        }
    }
}