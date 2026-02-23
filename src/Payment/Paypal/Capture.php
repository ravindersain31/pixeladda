<?php

namespace App\Payment\Paypal;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Service\Reward\RewardService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Capture extends Base
{
    private Order $order;

    private OrderTransaction $transaction;

    private string $token;

    private string $payerId;

    public function __construct(
        HttpClientInterface                     $client,
        ParameterBagInterface                   $parameterBag,
        UrlGeneratorInterface                   $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
    ) {
        parent::__construct($client, $parameterBag, $urlGenerator);
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function setTransaction(OrderTransaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }


    public function execute(bool $isExpress = false): array
    {
        $this->getToken();
        $result = $this->client->request('POST', '/v2/checkout/orders/' . $this->token . '/capture', [
            'base_uri' => $this->apiUrl[$this->env],
            'auth_bearer' => $this->accessToken,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        $data = json_decode($result->getContent(false), true);
        $response = $this->handleResponse($data);
        if ($isExpress && $response['success']) {
            $this->updateExpressOrder($data);
        }

        return $response;
    }

    private function updateExpressOrder($data): void
    {
        $payer = $data['payer'];
        $units = $data['purchase_units'];
        $shippingAddress = $this->makeShippingAddress($units, $payer);
        $billingAddress = $this->makeBillingAddress($payer, $units);
        $this->order->setShippingAddress($shippingAddress);
        $this->order->setBillingAddress($billingAddress);

        if (!$this->order->getUser()) {
            $this->order->setUser($this->userService->getUserFromAddress($billingAddress));
        }

        $this->entityManager->persist($this->order);
        $this->entityManager->flush();
    }

    private function handleResponse($response): array
    {

        if (isset($response['status']) && $response['status'] === 'COMPLETED') {
            $captures = [];
            foreach ($response['purchase_units'] as $unit) {
                foreach ($unit['payments']['captures'] as $capture) {
                    $captures[$capture['id']] = [
                        'id' => $capture['id'],
                        'amount' => floatval($capture['amount']['value']),
                        'currency' => $capture['amount']['currency_code'],
                        'status' => $capture['status'],
                        'refunded' => 0,
                    ];
                }
            }
            $this->transaction->setMetaDataKey('captures', $captures);

            $this->transaction->setMetaDataKey('payment_source', $response['payment_source']);
            $this->transaction->setMetaDataKey('payer_id', $this->payerId);
            $this->transaction->setComment('Payment completed.');

            $this->transaction->setStatus(PaymentStatusEnum::COMPLETED);
            $this->entityManager->persist($this->transaction);

            if (!$this->transaction->isIsPaymentLink()) {
                $orderStatus = $this->determineOrderStatus();
                $this->order->setStatus($orderStatus);
                $this->order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
                $this->order->setTotalReceivedAmount(floatval($this->order->getTotalReceivedAmount()) + floatval($this->transaction->getAmount()));
                $this->entityManager->persist($this->order);
            }

            $this->entityManager->flush();
            return [
                'success' => true,
                'message' => 'Payment completed successfully',
            ];
        }

        if ($this->transaction->getStatus() === PaymentStatusEnum::REDIRECTED_TO_GATEWAY) {
            if (!$this->transaction->isIsPaymentLink()) {
                $this->order->setStatus(OrderStatusEnum::RECEIVED);
                $this->order->setPaymentStatus(PaymentStatusEnum::FAILED);
                $this->entityManager->persist($this->order);
            }

            $this->transaction->setStatus(PaymentStatusEnum::FAILED);
            $this->transaction->setComment('Payment Failed');
            $this->entityManager->persist($this->transaction);

            $this->entityManager->flush();
            return [
                'success' => false,
                'message' => $this->getFailedMessage($response['name']),
            ];
        }
        return [
            'success' => false,
            'message' => $this->getFailedMessage(),
        ];
    }

    private function getFailedMessage($name = null): string
    {
        return match ($name) {
            'RESOURCE_NOT_FOUND' => 'Payment failed. [RESOURCE_NOT_FOUND]',
            'UNPROCESSABLE_ENTITY' => 'Payment failed. [UNPROCESSABLE_ENTITY]',
            default => 'Payment failed. [UNKNOWN]',
        };
    }

    private function makeShippingAddress(array $units, array $payer): array
    {
        $unit = $units[0];
        $fullName = $unit['shipping']['name']['full_name'];
        $firstName = substr($fullName, 0, strrpos($fullName, ' '));
        $givenName = substr($fullName, (strrpos($fullName, ' ') + 1));
        $address = $unit['shipping']['address'];
        return [
            'firstName' => $firstName,
            'lastName' => $givenName,
            'addressLine1' => $address['address_line_1'] ?? '',
            'addressLine2' => $address['address_line_2'] ?? '',
            'city' => $address['admin_area_2'],
            'state' => $address['admin_area_1'],
            'zipcode' => $address['postal_code'],
            'country' => $address['country_code'],
            'email' => $payer['email_address'],
            'phone' => ''
        ];
    }

    private function makeBillingAddress(array $payer, array $units): array
    {
        $unit = $units[0];
        $fullName = $unit['shipping']['name']['full_name'];
        $firstName = substr($fullName, 0, strrpos($fullName, ' '));
        $givenName = substr($fullName, (strrpos($fullName, ' ') + 1));
        $address = $unit['shipping']['address'];
        return [
            'firstName' => $payer['name']['given_name'] ?? $firstName,
            'lastName' => $payer['name']['surname'] ?? $givenName,
            'addressLine1' => $address['address_line_1'] ?? '',
            'addressLine2' => $address['address_line_2'] ?? '',
            'city' => $address['admin_area_2'],
            'state' => $address['admin_area_1'],
            'zipcode' => $address['postal_code'],
            'country' => $address['country_code'],
            'email' => $payer['email_address'],
            'phone' => ''
        ];
    }

    private function determineOrderStatus(): string
    {
        $cart = $this->order->getCart();
        $orderEligibleForAutoApproval = true;

        if ($cart instanceof Cart) {
            foreach ($cart->getCartItems() as $item) {
                $data = $item->getData();
                $isWireStake = isset($data['isWireStake']) && $data['isWireStake'] === true;
                $isBlankSign = isset($data['isBlankSign']) && $data['isBlankSign'] === true;

                if (!$isWireStake && !$isBlankSign) {
                    $orderEligibleForAutoApproval = false;
                    break;
                }
            }
        } else {
            $orderEligibleForAutoApproval = false;
        }

        if ($orderEligibleForAutoApproval || !$this->order->isNeedProof()) {
            return OrderStatusEnum::PROOF_APPROVED;
        }

        return OrderStatusEnum::RECEIVED;
    }
}
