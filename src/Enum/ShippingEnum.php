<?php

namespace App\Enum;

enum ShippingEnum
{
    const SHIPPING_EASY = 'SHIPPING_EASY';
    const EASYPOST = 'EASY_POST';

    const PICKUP = 'PICKUP';

    const FREIGHT = 'FREIGHT';

    const LABELS = [
        self::SHIPPING_EASY => 'Shipping Easy',
        self::EASYPOST => 'Easy Post',
        self::PICKUP => 'Pickup',
    ];

    const INTERNATIONAL_SHIPPING_CHARGE = 30.00;
}