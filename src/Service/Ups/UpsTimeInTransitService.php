<?php

namespace App\Service\Ups;

use App\Enum\Ups\UpsServiceLevel;
use App\Service\Ups\UpsServiceFilter;

class UpsTimeInTransitService extends AbstractUpsService
{
    public function getTransitTime(array $payload): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
        ];

        // if ($this->isSandbox()) {
            $headers['transId'] = 'test123';
            $headers['transactionSrc'] = 'testing';
        // }

        $response = $this->client->request('POST', $this->getBaseUrl() . '/api/shipments/v1/transittimes', [
            'headers' => $headers,
            'json' => $payload,
        ]);

        return $response->toArray();
    }

    public function retrieveDaysInTransit(TimeInTransit $payload): ?int
    {
        $data = [
            "originCountryCode" => $payload->getOriginCountryCode(),
            "originPostalCode" => $payload->getOriginPostalCode(),
            "destinationCountryCode" => $payload->getDestinationCountryCode(),
            "destinationStateProvince" => $payload->getDestinationStateProvince(),
        ];

        if ($payload->getDestinationCityName()) {
            $data['destinationCityName'] = $payload->getDestinationCityName();
        }
        if ($payload->getDestinationPostalCode()) {
            $data['destinationPostalCode'] = $payload->getDestinationPostalCode();
        }
        if ($payload->getWeight()) {
            $data['weight'] = $payload->getWeight();
        }
        if ($payload->getLength()) {
            $data['length'] = $payload->getLength();
        }
        if ($payload->getWidth()) {
            $data['width'] = $payload->getWidth();
        }
        if ($payload->getHeight()) {
            $data['height'] = $payload->getHeight();
        }

        $result = $this->getTransitTime($data);
        $simplified = UpsServiceFilter::filterAndSimplifyByServiceLevel($result, UpsServiceLevel::ground());
        $dayInTransit = UpsServiceFilter::filterSlowestServiceBusinessDays($simplified);
        return $dayInTransit;
    }
}
