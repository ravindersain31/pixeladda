<?php

namespace App\Enum;

enum OrderShipmentTypeEnum: string
{
    case DELIVERY = 'delivery';

    case RETURN = 'return';

    case EXCHANGE = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY => 'Delivery',
            self::RETURN => 'Return',
            self::EXCHANGE => 'Exchange',
        };
    }

}