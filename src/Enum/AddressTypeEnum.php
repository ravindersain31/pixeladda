<?php

namespace App\Enum;

enum AddressTypeEnum: string
{
    case SHIPPING = 'shippingAddress';
    case BILLING  = 'billingAddress';
}
