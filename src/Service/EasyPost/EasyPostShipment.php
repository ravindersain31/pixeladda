<?php

namespace App\Service\EasyPost;

use App\Enum\OrderShipmentTypeEnum;
use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPostShipment extends Base
{
    use EasyPostAwareTrait;

    private OrderShipmentTypeEnum $type = OrderShipmentTypeEnum::DELIVERY;

    private ?string $toAddressId = null;

    private ?string $parcelId = null;

    private EasyPostAddress $address;

    private EasyPostParcel $parcel;

    private ?array $customsInfo = null;

    private ?array $options = [];

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct($parameterBag);
        $this->address = new EasyPostAddress($parameterBag);
        $this->parcel = new EasyPostParcel($parameterBag);
    }

    public function get(string $shipmentId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->shipment->retrieve($shipmentId);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            $shipment = $this->parseShipment($response);
            return [
                'success' => true,
                'message' => 'Shipment retrieved successfully.',
                'shipment' => $shipment,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getRates(string $shipmentId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->shipment->regenerateRates($shipmentId);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            $data = $response->__toArray();
            $rates = $this->parseRates($data['rates']);
            return [
                'success' => true,
                'message' => 'Shipment rates retrieved successfully.',
                'rates' => $rates,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function buy(string $shipmentId, string|array $rate): array
    {
        $client = $this->getClient();
        try {
            if (!is_array($rate)) {
                $rate = ['id' => $rate];
            }
            $response = $client->shipment->buy($shipmentId, ['rate' => $rate]);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            $shipment = $this->parseShipment($response);
            return [
                'success' => true,
                'message' => 'Shipment bought successfully.',
                'shipment' => $shipment,
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
            $response = $client->shipment->create(['shipment' => $payload['shipment']]);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }
            $shipment = $this->parseShipment($response);
            return [
                'success' => true,
                'message' => 'Shipment created successfully.',
                'shipment' => $shipment,
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

    public function makePayload(?string $reference = null): array
    {
        if (!$this->toAddressId) {
            throw new \InvalidArgumentException('To address is required.');
        }

        if (!$this->parcelId) {
            throw new \InvalidArgumentException('Parcel is required.');
        }
        try {
            $toAddress = $this->getAddress($this->toAddressId);
            $fromAddress = $this->getAddress($this->getFromAddressId());
            $returnAddress = $this->getAddress($this->getReturnAddressId());
            $parcel = $this->getParcel($this->parcelId);
            $shipment = [
                'to_address' => $toAddress,
                'from_address' => $fromAddress,
                'return_address' => $returnAddress,
                'customs_info' => $this->getCustomsInfo(),
                'parcel' => $parcel,
                "carrier_accounts" => $this->getCarrierAccounts(),
            ];
            if ($this->getType() === OrderShipmentTypeEnum::RETURN) {
                $shipment['is_return'] = true;
            }
            if ($this->getOptions()) {
                $shipment['options'] = $this->getOptions();
            }
            if ($reference) {
                $shipment['reference'] = $reference;
            }
            return [
                'success' => true,
                'message' => 'Shipment payload created successfully.',
                'shipment' => $shipment,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getDaysInTransit(array $params, string $service = 'ground'): ?string
    {
        try {
            $results = $this->getClient()->smartRate->recommendShipDate($params)->__toArray();

            if (empty($results['results'])) {
                return null;
            }

            $daysInTransit = null;
            foreach ($results['results'] as $result) {
                if (isset($result['service']) && $result['service'] !== $service) {
                    continue;
                }

                if (!isset($result['easypost_time_in_transit_data'])) {
                    continue;
                }

                $parsedData = $this->parseDeliveries($result['easypost_time_in_transit_data']);

                if (!isset($parsedData['estimated_transit_days'])) {
                    continue;
                }

                $daysInTransit = $parsedData['estimated_transit_days'] ?? null;
            }
            return $daysInTransit;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getEstimatedDeliveryDate(array $params, string $deliveryDate): ?string
    {
        try {
            $results = $this->getClient()->smartRate->recommendShipDate($params)->__toArray();

            if (empty($results['results'])) {
                return $deliveryDate;
            }

            $slowestDate = null;
            foreach ($results['results'] as $result) {

                if (!isset($result['easypost_time_in_transit_data'])) {
                    continue;
                }
                $parsedData = $this->parseDeliveries($result['easypost_time_in_transit_data']);
                if (!isset($parsedData['ship_on_date'])) {
                    continue;
                }

                $date = $parsedData['ship_on_date'];

                if (is_null($slowestDate) || $date < $slowestDate) {
                    $slowestDate = $date;
                }
            }
            return (new \DateTimeImmutable($slowestDate ?? $deliveryDate))->format('Y-m-d');
        } catch (\Exception $e) {
            return $deliveryDate;
        }
    }

    public function setToAddressId(?string $toAddressId): void
    {
        $this->toAddressId = $toAddressId;
    }

    public function setParcelId(?string $parcelId): void
    {
        $this->parcelId = $parcelId;
    }

    public function getCustomsInfo(): ?array
    {
        return $this->customsInfo;
    }

    public function setCustomsInfo(?array $customsInfo): void
    {
        $this->customsInfo = $customsInfo;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    public function getType(): OrderShipmentTypeEnum
    {
        return $this->type;
    }

    public function setType(OrderShipmentTypeEnum $type): void
    {
        $this->type = $type;
    }
}