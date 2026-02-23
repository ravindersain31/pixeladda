<?php

namespace App\Service\EasyPost;

use EasyPost\EasyPostClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class Base
{

    // address from a production account https://www.easypost.com/account/shipping-settings?tab=sender-addresses
    private ?string $fromAddressId = 'adr_cac737ad91c711efaf9dac1f6bc539ae';

    // address from a production account https://www.easypost.com/account/shipping-settings?tab=sender-addresses
    private ?string $returnAddressId = 'adr_f61aab0691c711ef8d033cecef1b359e';

    private ?string $blindAddressId = 'adr_3df83587b7ad11efb0e43cecef1b359e';

    // [FedEx1, FedEx2]
    private array $carrierAccounts = ['ca_134ed6a936a94f539c1a21d04ecf23fb', 'ca_0812967054a543239e3edc718506defb'];

    private EasyPostClient $client;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $env = $parameterBag->get('EASYPOST_ENV');
        if ($env === 'test') {
            $this->fromAddressId = 'adr_7430540f8d2b11efa387ac1f6bc539aa';
            $this->returnAddressId = 'adr_a88e4ffd9dc411ef941aac1f6bc539aa';
            $this->blindAddressId = 'adr_6be60232b7ad11efa84eac1f6bc53342';
            // [FedEx]
            $this->carrierAccounts = ['ca_164b2a3de47d48c990da923bd018ee48'];
        }
        $apiKey = $parameterBag->get('EASYPOST_KEY');
        $this->client = new EasyPostClient($apiKey);
    }

    public function getClient(): EasyPostClient
    {
        return $this->client;
    }

    public function getFromAddressId(): ?string
    {
        return $this->fromAddressId;
    }

    public function setFromAddressId(?string $fromAddressId): void
    {
        $this->fromAddressId = $fromAddressId;
    }

    public function getReturnAddressId(): ?string
    {
        return $this->returnAddressId;
    }

    public function setReturnAddressId(?string $returnAddressId): void
    {
        $this->returnAddressId = $returnAddressId;
    }

    public function getBlindAddressId(): ?string
    {
        return $this->blindAddressId;
    }

    public function setBlindAddressId(?string $blindAddressId): void
    {
        $this->blindAddressId = $blindAddressId;
    }

    public function getCarrierAccounts(): array
    {
        return $this->carrierAccounts;
    }

    protected function parseErrorsAsMessage(array $errors): string
    {
        $message = '';
        foreach ($errors as $error) {
            if (isset($error['field'])) {
                $message .= $error['field'] . ': ';
            }
            $message .= $error['message'] . ' ';
        }
        return $message;
    }

}