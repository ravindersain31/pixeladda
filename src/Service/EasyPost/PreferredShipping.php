<?php

namespace App\Service\EasyPost;

use App\Entity\Order;
use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PreferredShipping
{
    public const FEDEX_DEFAULT = 'FEDEXDEFAULT';
    public const UPS = 'UPS';
    public const USPS = 'USPS';
    public const UPSDAP = 'UPSDAP';

    public function get(?array $rates, Order $order): array
    {
        $groupedRates = $this->groupByCarrier($rates);

        $carrier = null;
        if (isset($groupedRates[self::FEDEX_DEFAULT])) {
            $carrier = self::FEDEX_DEFAULT;
        } elseif (isset($groupedRates[self::UPSDAP]) && isset($groupedRates[self::UPS])) {
            $carrier = self::UPSDAP;
        } elseif (isset($groupedRates[self::UPSDAP])) {
            $carrier = self::UPSDAP;
        } elseif (isset($groupedRates[self::UPS])) {
            $carrier = self::UPS;
        } else {
            $carrier = array_key_first($groupedRates);
        }

        if (!in_array($carrier, [self::FEDEX_DEFAULT])) {
            return [
                'selectedRate' => null,
                'options' => [
                    ...$groupedRates,
                ]
            ];
        }

        $selectedRateId = null;
        $ups = $groupedRates[$carrier]['rates'] ?? [];
        $rates = $this->getRatesByCarrier($ups, $carrier, $order->getDeliveryDate());

        $groupedRates[$carrier]['rates'] = $rates['remainingRates'];

        if ($rates['preferredRate']) {
            $selectedRateId = $rates['preferredRate']['id'];
        } elseif ($rates['lowestRate'] && $rates['lowestRate']['carrier'] !== 'USPS') {
            $selectedRateId = $rates['lowestRate']['id'];
        }

        $options = [];
        if ($rates['preferredRate']) {
            $options['Preferred'] = ['title' => 'Preferred', 'rates' => [$rates['preferredRate']]];
        }

        $options = array_merge($options, $groupedRates);

        return [
            'selectedRate' => $selectedRateId,
            'options' => $options,
        ];
    }

    public function getRatesByCarrier(array $rates, string $carrier, \DateTimeImmutable $orderDeliveryDate): array
    {
        // Filter rates to include only those from the specified carrier
        $filteredRates = array_filter($rates, function ($rate) use ($carrier) {
            return  strtoupper($rate['carrier']) === $carrier;
        });

        if (empty($filteredRates)) {
            return [
                'lowestRate' => null,
                'remainingRates' => $rates // Return all rates if no rates found for the carrier
            ];
        }

        $preferredRates = [];
        foreach ($filteredRates as $rate) {
            if (!empty($rate['delivery_days'])) {
                $deliveryDate = (new \DateTimeImmutable())->modify("+{$rate['delivery_days']} days");

                if ($deliveryDate <= $orderDeliveryDate) {
                    $preferredRates[] = $rate;
                }
            }
        }

        usort($preferredRates, function ($a, $b) {
            return $a['rate'] <=> $b['rate'];
        });

        $preferredRate = null;
        if ($preferredRates) {
            $preferredRate = array_shift($preferredRates);
        }

        usort($filteredRates, function ($a, $b) {
            $deliveryDaysA = isset($a['delivery_days']) ? $a['delivery_days'] : PHP_INT_MAX;
            $deliveryDaysB = isset($b['delivery_days']) ? $b['delivery_days'] : PHP_INT_MAX;

            if ($deliveryDaysA === $deliveryDaysB) {
                return $a['rate'] <=> $b['rate'];
            }
            return $deliveryDaysA <=> $deliveryDaysB;
        });

        $lowestRate = reset($filteredRates);

        if ($preferredRate) {
            $remainingRates = array_filter($rates, function ($rate) use ($preferredRate) {
                return $rate['id'] !== $preferredRate['id'];
            });
        } else {
            $remainingRates = $filteredRates;
        }

        return [
            'preferredRate' => $preferredRate,
            'lowestRate' => $lowestRate,
            'remainingRates' => array_values($remainingRates) // Re-index array
        ];
    }

    public function groupByCarrier(array $rates): array
    {
        $groupedRates = [];

        foreach ($rates as $rate) {
            $carrier = strtoupper($rate['carrier']);

            $title = $carrier;
            if ($carrier === 'UPSDAP') {
                $title = 'UPS';
            }
            if (!isset($groupedRates[$carrier])) {
                $groupedRates[$carrier] = [
                    'title' => $title,
                    'rates' => []
                ];
            }

            $groupedRates[$carrier]['rates'][] = $rate;
        }

        $preferredOrder = [self::FEDEX_DEFAULT, self::UPSDAP, self::USPS];

        // Sort the grouped rates by the preferred order
        uksort($groupedRates, function ($key1, $key2) use ($preferredOrder) {
            $index1 = array_search($key1, $preferredOrder);
            $index2 = array_search($key2, $preferredOrder);

            // Set unknown carriers to appear at the end
            $index1 = $index1 === false ? PHP_INT_MAX : $index1;
            $index2 = $index2 === false ? PHP_INT_MAX : $index2;

            return $index1 <=> $index2;
        });

        return $groupedRates;
    }
}