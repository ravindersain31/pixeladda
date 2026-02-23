<?php

namespace App\Service\EasyPost;

use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPostOrder extends Base
{
    use EasyPostAwareTrait;

    private ?string $toAddressId = null;

    private ?array $shipments = null;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct($parameterBag);
        $this->address = new EasyPostAddress($parameterBag);
        $this->parcel = new EasyPostParcel($parameterBag);
    }

    public function get(string $orderId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->order->retrieve($orderId);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            $order = $this->parseOrderResponse($response);
            return [
                'success' => true,
                'message' => 'Order retrieved successfully.',
                'order' => $order,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getRates(string $orderId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->order->getRates($orderId);
            $data = $response->__toArray();
            $rates = $this->parseRates($data['rates']);
            return [
                'success' => true,
                'message' => 'Order rates retrieved successfully.',
                'rates' => $rates,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function buy(string $orderId, string $carrier, string $service): array
    {
        $client = $this->getClient();

        try {
            $response = $client->order->buy($orderId, [
                'carrier' => $carrier,
                'service' => $service,
            ]);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            $order = $this->parseOrderResponse($response);
            return [
                'success' => true,
                'message' => 'Order labels bought successfully.',
                'order' => $order,
            ];
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function create(?string $reference = null): array
    {
        $client = $this->getClient();
        $payload = $this->makePayload($reference);
        if (!$payload['success']) {
            return $payload;
        }

        try {
            $response = $client->order->create($payload['order']);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            $order = $this->parseOrderResponse($response);
            return [
                'success' => true,
                'message' => 'Order created successfully.',
                'order' => $order,
            ];
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function makePayload(?string $reference = null): array
    {
        if (!$this->toAddressId) {
            throw new \InvalidArgumentException('To address is required.');
        }

        if (!$this->shipments) {
            throw new \InvalidArgumentException('Shipments is required.');
        }
        try {
            $toAddress = $this->getAddress($this->toAddressId);
            $fromAddress = $this->getAddress($this->getFromAddressId());
            $returnAddress = $this->getAddress($this->getReturnAddressId());
            $order = [
                'to_address' => $toAddress,
                'from_address' => $fromAddress,
                'return_address' => $returnAddress,
                'shipments' => $this->shipments,
                "carrier_accounts" => $this->getCarrierAccounts(),
            ];
            if ($reference) {
                $order['reference'] = $reference;
            }
            return [
                'success' => true,
                'message' => 'Order payload created successfully.',
                'order' => $order,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function parseOrderResponse(mixed $response): array
    {
        $data = $response->__toArray();
        $toAddress = $data['to_address']->__toArray();
        $fromAddress = $data['from_address']->__toArray();
        $buyerAddress = $data['buyer_address']->__toArray();
        $returnAddress = $data['return_address']->__toArray();
        $shipments = $this->parseShipments($data['shipments']);
        return [
            'id' => $data['id'],
            'to_address' => $toAddress['id'],
            'from_address' => $fromAddress['id'],
            'buyer_address' => $buyerAddress['id'],
            'return_address' => $returnAddress['id'],
            'shipments' => $shipments,
        ];
    }

    public function setToAddressId(?string $toAddressId): void
    {
        $this->toAddressId = $toAddressId;
    }

    public function setShipments(?array $shipments): void
    {
        $this->shipments = $shipments;
    }

}