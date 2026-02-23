<?php

namespace App\Enum\Admin;

enum WarehouseShippingServiceEnum
{
    const UPS_NEXT_DAY_AIR_SAVER = 'UPS_NEXT_DAY_AIR_SAVER';
    const UPS_2ND_DAY_AIR = 'UPS_2ND_DAY_AIR';
    const UPS_3_DAY_SELECT = 'UPS_3_DAY_SELECT';
    const UPS_GROUND = 'UPS_GROUND';
    const USPS_PRIORITY_MAIL = 'USPS_PRIORITY_MAIL';
    const FEDEX_INTERNATIONAL_ECONOMY = 'FEDEX_INTERNATIONAL_ECONOMY';
    const FEDEX_INTERNATIONAL_PRIORITY = 'FEDEX_INTERNATIONAL_PRIORITY';

    const FEDEX_GROUND = 'FEDEX_GROUND';
    const FEDEX_HOME = 'FEDEX_HOME';
    // const FEDEX_GROUND_HOME = 'FEDEX_GROUND_HOME'; // Deprecated,
    const FEDEX_STANDARD_OVERNIGHT = 'FEDEX_STANDARD_OVERNIGHT';
    const FEDEX_2_DAY = 'FEDEX_2_DAY';
    // const FEDEX_EXPRESS_SAVER = 'FEDEX_EXPRESS_SAVER';

    const SHIPPING_SERVICE_ORDER = [
        self::UPS_NEXT_DAY_AIR_SAVER => 1,
        self::FEDEX_STANDARD_OVERNIGHT => 2,
        self::UPS_2ND_DAY_AIR => 3,
        self::FEDEX_2_DAY => 4,
        self::UPS_3_DAY_SELECT => 5,
        self::UPS_GROUND => 6,
        self::FEDEX_GROUND => 7,
        self::FEDEX_HOME => 8,
        self::USPS_PRIORITY_MAIL => 9,
        self::FEDEX_INTERNATIONAL_ECONOMY => 10,
        self::FEDEX_INTERNATIONAL_PRIORITY => 11,
        // self::FEDEX_EXPRESS_SAVER => 12,
    ];

    const SHIPPING_SERVICE = [
        self::UPS_NEXT_DAY_AIR_SAVER => [
            'label' => 'UPS Next Day Air Saver',
        ],
        self::UPS_2ND_DAY_AIR => [
            'label' => 'UPS 2nd Day Air',
        ],
        self::UPS_3_DAY_SELECT => [
            'label' => 'UPS 3 Day Select',
        ],
        self::UPS_GROUND => [
            'label' => 'UPS Ground',
        ],
        self::USPS_PRIORITY_MAIL => [
            'label' => 'USPS Priority Mail',
        ],
        self::FEDEX_INTERNATIONAL_ECONOMY => [
            'label' => 'FedEx International Economy',
        ],
        self::FEDEX_INTERNATIONAL_PRIORITY => [
            'label' => 'FedEx International Priority',
        ],
        self::FEDEX_GROUND => [
            'label' => 'FedEx Ground',
        ],
        self::FEDEX_HOME => [
            'label' => 'FedEx Home Delivery',
        ],
        self::FEDEX_STANDARD_OVERNIGHT => [
            'label' => 'FedEx Standard Overnight',
        ],
        self::FEDEX_2_DAY => [
            'label' => 'FedEx 2Day',
        ],
        // self::FEDEX_EXPRESS_SAVER => [
        //     'label' => 'FedEx Express Saver (3 Day)',
        // ]
    ];

    const FEDEX_SHIPPING_SERVICE = [
        self::FEDEX_STANDARD_OVERNIGHT => self::SHIPPING_SERVICE[self::FEDEX_STANDARD_OVERNIGHT],
        self::FEDEX_2_DAY => self::SHIPPING_SERVICE[self::FEDEX_2_DAY],
        // self::FEDEX_EXPRESS_SAVER => self::SHIPPING_SERVICE[self::FEDEX_EXPRESS_SAVER],
        self::FEDEX_HOME => self::SHIPPING_SERVICE[self::FEDEX_HOME],
    ];

    public static function makeFormChoices(): array
    {
        $choices = [];
        foreach (self::FEDEX_SHIPPING_SERVICE as $key => $service) {
            $choices[$service['label']] = $key;
        }
        return $choices;
    }

    public static function getLabel(string $service): string
    {
        if (!isset(self::SHIPPING_SERVICE[$service])) {
            return $service;
        }
        return self::SHIPPING_SERVICE[$service]['label'];
    }
}
