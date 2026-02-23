<?php

namespace App\Enum;

enum ShippingStatusEnum
{
    const READY_FOR_SHIPMENT = 'ready_for_shipment';

    const SHIPMENT_CREATED = 'shipment_created';

    const LABEL_PURCHASED = 'label_purchased';

    const SHIPPED = 'shipped';
}