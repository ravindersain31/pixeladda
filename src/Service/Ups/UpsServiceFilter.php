<?php

namespace App\Service\Ups;

use App\Enum\Ups\UpsServiceLevel;

class UpsServiceFilter
{
    /**
     * Filters UPS services based on provided filters.
     *
     * @param array $response The full emsResponse array from UPS
     * @param array $filters  Associative array of key => value to filter on
     * @return array Filtered list of UPS services
     */
    public static function filterServices(array $response, array $filters = []): array
    {
        if (!isset($response['emsResponse']['services'])) {
            return [];
            // throw new \InvalidArgumentException("Invalid UPS response structure. 'services' key missing.");
        }

        $services = $response['emsResponse']['services'];

        // Filter the services array
        $filtered = array_filter($services, function ($service) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($service[$key])) {
                    return false;
                }

                // Allow value to be an array for IN-like filtering
                if (is_array($value)) {
                    if (!in_array($service[$key], $value)) {
                        return false;
                    }
                } else {
                    if ($service[$key] != $value) {
                        return false;
                    }
                }
            }
            return true;
        });

        return array_values($filtered); // Reset indexes
    }

    /**
     * Returns simplified service info (name, date, time, guarantee).
     *
     * @param array $services Filtered services
     * @return array List of simplified service info
     */
    public static function simplify(array $services): array
    {
        return array_map(function ($s) {
            return [
                'serviceLevel' => $s['serviceLevel'] ?? null,
                'description' => $s['serviceLevelDescription'] ?? null,
                'deliveryDate' => $s['deliveryDate'] ?? null,
                'deliveryTime' => $s['deliveryTime'] ?? null,
                'guaranteed' => isset($s['guaranteeIndicator']) && $s['guaranteeIndicator'] === '1',
                'businessDays' => $s['businessTransitDays'] ?? null,
                'pickupTime' => $s['pickupTime'] ?? null,
                'cstCutoffTime' => $s['cstccutoffTime'] ?? null,
            ];
        }, $services);
    }

    /**
     * Filters services by service level enum group (e.g., ground, nextDay) and simplifies output.
     *
     * @param array $response The full UPS API response
     * @param array<UpsServiceLevel> $serviceLevels Enum array like UpsServiceLevel::ground()
     * @return array Simplified service data
     */
    public static function filterAndSimplifyByServiceLevel(array $response, array $serviceLevels): array
    {
        $filtered = self::filterServices($response, [
            'serviceLevel' => array_map(fn($e) => $e->value, $serviceLevels),
        ]);

        return self::simplify($filtered);
    }

    public static function filterSlowestServiceBusinessDays(array $services): ?int
    {
        if (empty($services)) {
            return null;
        }

        $maxDays = null;

        foreach ($services as $service) {
            if (isset($service['businessDays'])) {
                if ($maxDays === null || $service['businessDays'] > $maxDays) {
                    $maxDays = $service['businessDays'];
                }
            }
        }

        return $maxDays;
    }

}