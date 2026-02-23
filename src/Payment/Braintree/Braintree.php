<?php

namespace App\Payment\Braintree;

use App\Entity\AppUser;
use App\Entity\Order;
use App\Payment\PaymentInterface;
use Braintree\Result\Error;
use Braintree\Result\Successful;

class Braintree extends Base implements PaymentInterface
{
    private ?string $actionOnSuccess = null;

    public function charge(Order $order, float $customAmount = 0): array
    {
        $customer = $order->getUser();

        $braintreeId = null;
        if ($customer instanceof AppUser) {
            $braintreeId = $customer->getBraintreeId();
        }

        $billingAddress = $order->getBillingAddress();
        if (!$braintreeId && $customer instanceof AppUser) {
            $braintreeId = $this->createCustomer($customer, $billingAddress);
        }


        $sale = [
            'amount' => $customAmount > 0 ? $customAmount : $order->getTotalAmount(),
            'paymentMethodNonce' => $this->paymentNonce,
            'customFields' => $this->customFields,
            'options' => [
                'submitForSettlement' => true
            ],
            'billing' => [
                'firstName' => $billingAddress['firstName'],
                'lastName' => $billingAddress['lastName'],
                'postalCode' => $billingAddress['zipcode'],
                'streetAddress' => $billingAddress['addressLine1'],
                'countryCodeAlpha2' => $billingAddress['country'],
            ]
        ];

        if ($braintreeId) {
            $sale['customerId'] = $braintreeId;
        }

        $result = $this->gateway->transaction()->sale($sale);

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

    private function createCustomer(AppUser $user, array $billingAddress): null|string
    {
        $result = $this->gateway->customer()->create([
            'firstName' => $user->getFirstName() ?? $billingAddress['firstName'],
            'lastName' => $user->getLastName() ?? $billingAddress['lastName'],
            'email' => $user->getEmail(),
        ]);
        if ($result->success) {
            $customerId = $result->customer->id;
            $user->setBraintreeId($customerId);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $customerId;
        }
        return null;
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }

    public function createPaymentMethod(
        AppUser $user,
        string $paymentMethodNonce,
        array $userInfo
    ): array {
        $braintreeId = $user->getBraintreeId();

        if (!$braintreeId) {
            $braintreeId = $this->createCustomer($user, $userInfo);
        }

        $result = $this->gateway->paymentMethod()->create([
            'customerId' => $braintreeId,
            'paymentMethodNonce' => $paymentMethodNonce,
            'options' => [
                'verifyCard' => true,
                'makeDefault' => true,
            ]
        ]);

        if (!$result->success) {
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'message' => $result->message,
                    'details' => array_map(
                        static fn($e) => [
                            'code' => $e->code,
                            'message' => $e->message,
                        ],
                        $result->errors->deepAll()
                    ),
                ],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'token' => $result->paymentMethod->token,
                'cardType' => $result->paymentMethod->cardType,
                'last4' => $result->paymentMethod->last4,
                'expMonth' => $result->paymentMethod->expirationMonth,
                'expYear' => $result->paymentMethod->expirationYear,
            ],
            'error' => null,
        ];
    }

    public function deletePaymentMethod(string $token): void
    {
        $this->gateway->paymentMethod()->delete($token);
    }

    public function chargeWithToken(
        Order $order,
        string $paymentMethodToken,
        float $customAmount = 0
    ): array {
        $result = $this->gateway->transaction()->sale([
            'amount' => $customAmount > 0 ? $customAmount : $order->getTotalAmount(),
            'paymentMethodToken' => $paymentMethodToken,
            'options' => [
                'submitForSettlement' => true
            ]
        ]);

        return $this->handleResult($result);
    }
}