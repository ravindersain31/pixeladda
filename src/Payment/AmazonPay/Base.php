<?php

namespace App\Payment\AmazonPay;

use Amazon\Pay\API\Client;
use App\Payment\AbstractPayment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class Base extends AbstractPayment
{
    protected Client $client;
    protected string $checkoutReviewReturnUrl;

    protected string $publicId;
    protected string $region;
    protected string $algoKey;
    protected string $storeId;
    protected bool $sandboxMode;

    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();
        $privateKeyPath = $kernel->getProjectDir() . '/' . $params->get('AMAZON_PAY_PUBLIC_FILE');
        $privateKeyContent = trim(file_get_contents($privateKeyPath));

        $this->publicId = $params->get('AMAZON_PAY_PUBLIC_ID');
        $this->region = $params->get('AMAZON_PAY_REGION', 'us');
        $this->algoKey = $params->get('AMAZON_ALGO_KEY', 'AMZN-PAY-RSASSA-PSS-V2');
        $this->storeId = $params->get('AMAZON_PAY_STORE_ID');
        $this->sandboxMode = $params->get('AMAZON_PAY_ENV') === 'sandbox';

        $this->client = new Client([
            'public_key_id' => $this->publicId,
            'private_key'   => $privateKeyContent,
            'region'        => $this->region,
            'sandbox'       => $this->sandboxMode,
            'algorithm'     => $this->algoKey,
        ]);

        $this->checkoutReviewReturnUrl = $request?->getUri() ?? 'https://localhost/';
    }
}
