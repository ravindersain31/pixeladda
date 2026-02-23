<?php

namespace App\Service\EasyPost;

use EasyPost\Exception\Api\InvalidRequestException;

trait EasyPostAwareTrait
{
    private EasyPostAddress $address;

    private EasyPostParcel $parcel;

    public function getAddress(string $addressId): array
    {
        $response = $this->address->get($addressId);
        if (!$response['success']) {
            throw new \Exception($response['message']);
        }
        return $response['address'];
    }

    public function getParcel(string $parcelId): array
    {
        $response = $this->parcel->get($parcelId);
        if (!$response['success']) {
            throw new \Exception($response['message']);
        }
        return $response['parcel'];
    }

    public function getRate(string $rateId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->rate->retrieve($rateId);
            $rate = $this->parseRate($response);
            return [
                'success' => true,
                'message' => 'Rate retrieved successfully.',
                'rate' => $rate,
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

    public function parseShipments(array $shipments): array
    {
        return array_map(function ($shipment) {
            return $this->parseShipment($shipment);
        }, $shipments);
    }

    public function parseShipment(mixed $shipment): array
    {
        $data = $shipment->__toArray();
        $toAddress = $data['to_address']->__toArray();
        $fromAddress = $data['from_address']->__toArray();
        $parcel = $data['parcel']->__toArray();

        $shipmentData = [
            'id' => $data['id'],
            'status' => $data['status'],
            'insurance' => $data['insurance'],
            'to_address' => $toAddress['id'],
            'from_address' => $fromAddress['id'],
            'parcel' => [
                'id' => $parcel['id'],
                'length' => $parcel['length'],
                'width' => $parcel['width'],
                'height' => $parcel['height'],
                'weight' => $parcel['weight'],
            ],
            'tracking_code' => $data['tracking_code'],
            'updated_at' => $data['updated_at'],
            'created_at' => $data['created_at'],
            'customs_info' => $data['customs_info']
        ];

        if ($data['postage_label']) {
            $postageLabel = $data['postage_label']->__toArray();
            $shipmentData['postage_label'] = [
                'id' => $postageLabel['id'],
                'label_date' => $postageLabel['label_date'],
                'label_url' => $postageLabel['label_url'],
                'label_file_type' => $postageLabel['label_file_type'],
            ];
        }

        if ($data['selected_rate']) {
            $shipmentData['selected_rate'] = $this->parseRate($data['selected_rate']);
        }

        if ($data['rates']) {
            $shipmentData['rates'] = $this->parseRates($data['rates']);
        }

        return $shipmentData;
    }

    private function parseRates(array $rates): array
    {
        return array_map(function ($rate) {
            return $this->parseRate($rate);
        }, $rates);
    }

    public function parseRate(mixed $rate): array
    {
        $data = $rate->__toArray();
        return [
            'id' => $data['id'],
            'shipment_id' => $data['shipment_id'],
            'rate' => $data['rate'],
            'currency' => $data['currency'],
            'carrier' => $data['carrier'],
            'service' => $data['service'],
            'delivery_days' => $data['delivery_days'],
            'est_delivery_days' => $data['est_delivery_days'],
        ];
    }

    public function parseMessages(mixed $response): array
    {
        $data = $response->__toArray();
        if (isset($data['messages'])) {
            $messages = $data['messages'];
            foreach ($messages as $message) {
                $messageData = $message->__toArray();
                if (str_contains($messageData['message'], 'error')) {
                    return [
                        'success' => false,
                        'message' => $messageData['message'],
                        'error' => $messageData,
                    ];
                }
            }
        }
        return [
            'success' => true,
            'message' => 'No error messages found.',
        ];
    }

    public function parseDeliveries(mixed $delivery): array
    {
        try {
            $data = $delivery->__toArray();

            if (!isset($data['ship_on_date'], $data['estimated_transit_days'], $data['delivery_date_confidence'])) {
                return [];
            }

            return [
                'ship_on_date' => $data['ship_on_date'],
                'estimated_transit_days' => $data['estimated_transit_days'],
                'delivery_date_confidence' => $data['delivery_date_confidence'],
            ];
        } catch (\Exception $e) {
            return [];
        }
    }


}