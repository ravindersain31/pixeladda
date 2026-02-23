<?php

namespace App\Enum;

enum OrderTagsEnum
{
    const BLIND_SHIPPING = 'BLIND_SHIPPING';
    const REQUEST_PICKUP = 'REQUEST_PICKUP';
    const SAMPLE = 'SAMPLE';
    const DIE_CUT = 'DIE_CUT';
    const DELAYED = 'DELAYED';
    const SCORING = 'SCORING';
    const FREIGHT = 'FREIGHT';
    const SATURDAY_DELIVERY = 'SATURDAY_DELIVERY';
    const RUSH = 'RUSH';
    const SUPER_RUSH = 'SUPER_RUSH';
    const REPEAT_ORDER = 'REPEAT_ORDER';

    const LABELS = [
        self::BLIND_SHIPPING => 'Blind Shipping',
        self::REQUEST_PICKUP => 'Request Pickup',
        self::SAMPLE => 'Sample',
        self::DIE_CUT => 'Die Cut',
        // self::DELAYED => 'Delayed',
        self::SCORING => 'Scoring',
        self::FREIGHT => 'Freight',
        self::SATURDAY_DELIVERY => 'Saturday Delivery',
        // self::RUSH => 'Rush',
        // self::SUPER_RUSH => 'Super Rush',
        self::REPEAT_ORDER => 'Repeat Order',
    ];

    const ALL_TAGS = [
        self::BLIND_SHIPPING => 'Blind Shipping',
        self::REQUEST_PICKUP => 'Request Pickup',
        self::SAMPLE => 'Sample',
        self::DIE_CUT => 'Die Cut',
        self::DELAYED => 'Delayed',
        self::SCORING => 'Scoring',
        self::FREIGHT => 'Freight',
        self::SATURDAY_DELIVERY => 'Saturday Delivery',
        self::RUSH => 'Rush',
        self::SUPER_RUSH => 'Super Rush',
        self::REPEAT_ORDER => 'Repeat Order',
    ];
}
