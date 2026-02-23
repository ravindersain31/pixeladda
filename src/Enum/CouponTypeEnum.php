<?php
 
namespace App\Enum;
 
enum CouponTypeEnum: string
{
    case PERCENTAGE = 'P';
    case FLAT = 'F';
    case STANDARD = 'STANDARD';
    case AFFILIATE = 'AFFILIATE';
    case REFERRAL = 'REFERRAL';
    case REFERRAL_V1 = 'referral_coupon';
    case EMPTY = '';
 
    public static function discountTypes(): array
    {
        return [
            self::PERCENTAGE => 'Percentage',
            self::FLAT => 'Flat',
        ];
    }
 
    public static function types(): array
    {
        return [
            self::STANDARD => 'Standard',
            self::AFFILIATE => 'Affiliate',
            self::REFERRAL => 'Referral',
        ];
    }
}