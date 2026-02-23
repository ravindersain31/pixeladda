<?php

namespace App\Enum;

enum OrderChannelEnum: string
{
    case CHECKOUT = 'CHECKOUT';
    case EXPRESS = 'EXPRESS';
    case REPLACEMENT = 'REPLACEMENT';
    case SM3 = 'SM3';
    case SALES = 'SALES';
    case SPLIT_ORDER = 'SPLIT_ORDER';
    case SALE = 'SALE';

    public function label(): string
    {
        return match ($this) {
            self::CHECKOUT => 'Checkout',
            self::EXPRESS => 'Express',
            self::REPLACEMENT => 'Replacement',
            self::SM3 => 'SM3',
            self::SALES, self::SALE => 'Sales',
            self::SPLIT_ORDER => 'Split Order',
        };
    }

    public function isEmailNotification(): bool
    {
        return match ($this) {
            self::CHECKOUT,
            self::EXPRESS,
            self::SALES, self::SALE => true,
            self::REPLACEMENT,
            self::SM3 => false,
        };
    }

    public function isSmsNotification(): bool
    {
        return match ($this) {
            self::CHECKOUT,
            self::EXPRESS,
            self::SALES, self::SALE => true,
            self::REPLACEMENT,
            self::SM3 => false,
        };
    }

    public function isCallNotification(): bool
    {
        return match ($this) {
            self::CHECKOUT,
            self::EXPRESS,
            self::SALES, self::SALE => true,
            self::REPLACEMENT,
            self::SM3 => false,
        };
    }
}