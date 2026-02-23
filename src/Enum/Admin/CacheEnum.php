<?php

namespace App\Enum\Admin;

enum CacheEnum: string
{
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case CUSTOMER_PHOTOS = 'customer_photos';
    case PRODUCT_TYPE = 'product_type';
    case STORE = 'store';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => 'Product',
            self::CATEGORY => 'Category',
            self::CUSTOMER_PHOTOS => 'Customer Photos',
            self::PRODUCT_TYPE => 'Product Type',
            self::STORE => 'Store',
        };
    }

    public function ttl(): int
    {
        return match ($this) {
            self::PRODUCT,
            self::CATEGORY,
            self::CUSTOMER_PHOTOS,
            self::PRODUCT_TYPE => 2592000, // 1 month
            self::STORE => 15552000, // 6 months
        };
    }

    public static function all(): array
    {
        return [
            self::PRODUCT,
            self::CATEGORY,
            self::CUSTOMER_PHOTOS,
            self::PRODUCT_TYPE,
            self::STORE,
        ];
    }

    public static function tryFromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }
}
