<?php

namespace App\Service;

use Braintree\Gateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ChargeCardService
{

    private Gateway $gateway;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        public readonly EntityManagerInterface $entityManager,
        public readonly RequestStack           $requestStack,
    )
    {
        $this->gateway = new Gateway([
            'environment' => $this->parameterBag->get('BRAINTREE_ENV'),
            'merchantId' => $this->parameterBag->get('BRAINTREE_MERCHANT_ID'),
            'publicKey' => $this->parameterBag->get('BRAINTREE_PUBLIC_KEY'),
            'privateKey' => $this->parameterBag->get('BRAINTREE_PRIVATE_KEY'),
        ]);
    }

    public function charge(float $amount, array $customFields)
    {
        $result = $this->gateway->transaction()->sale([
            'amount' => $amount,
            'paymentMethodNonce' => $this->paymentNonce,
            'customFields' => $customFields,
            'options' => [
                'submitForSettlement' => true
            ]
        ]);

        return $this->handleResult($result);
    }


    private function handleResult(Successful|Error $result): array
    {
        return match (true) {
            $result instanceof Successful => $this->handleSuccess($result),
            $result instanceof Error => $this->handleError($result),
        };
    }

    private function handleSuccess(Successful $result): array
    {
        return [
            'success' => true,
            'action' => 'completed',
            'message' => 'Payment successful',
            'transaction' => [
                'gatewayId' => $result->transaction->id,
                'type' => $result->transaction->type,
                'status' => $result->transaction->status,
                'amount' => $result->transaction->amount,
                'currency' => $result->transaction->currencyIsoCode,
                'receipt' => $result->transaction->paymentReceipt,
            ],
        ];
    }

    private function handleError(Error $result): array
    {
        return [
            'success' => false,
            'action' => 'retry',
            'message' => $result->message,
            'transaction' => $result->params['transaction'],
        ];
    }

}