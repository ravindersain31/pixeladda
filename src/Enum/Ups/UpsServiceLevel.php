<?php

namespace App\Enum\Ups;

enum UpsServiceLevel: string
{
    // 🕒 Next Day Services
    case NEXT_DAY_AIR_EARLY_ALIAS   = '1DMS'; // alias
    case NEXT_DAY_AIR_STANDARD      = '1DAS';
    case NEXT_DAY_AIR_EARLY         = '1DM';
    case NEXT_DAY_AIR               = '1DA';
    case NEXT_DAY_AIR_SAVER         = '1DP';

    // 📦 2nd Day Services
    case SECOND_DAY_AIR_AM          = '2DM';
    case SECOND_DAY_AIR             = '2DA';

    // 📦 3rd Day
    case THREE_DAY_SELECT           = '3DS';

    // 🚛 Ground
    case GROUND                     = 'GND';

    // 🛫 International
    case WORLDWIDE_EXPRESS_PLUS     = 'EXPPL';
    case WORLDWIDE_EXPRESS          = 'EXP';
    case WORLDWIDE_EXPEDITED        = 'EXPD';
    case STANDARD                   = 'STD';
    case SAVER                      = 'SAV';
    case WORLDWIDE_ECONOMY_DDU      = 'WWE'; // economy with duties unpaid
    case WORLDWIDE_ECONOMY_DDP      = 'WWEDDP'; // economy with duties paid

    // 🛍 Retail-focused services
    case ACCESS_POINT               = 'UPSAP';
    case SUREPOST                   = 'SP'; // with USPS last mile

    // 🎯 Utility methods for categories
    public static function nextDay(): array
    {
        return [
            self::NEXT_DAY_AIR_EARLY_ALIAS,
            self::NEXT_DAY_AIR_STANDARD,
            self::NEXT_DAY_AIR_EARLY,
            self::NEXT_DAY_AIR,
            self::NEXT_DAY_AIR_SAVER,
        ];
    }

    public static function secondDay(): array
    {
        return [
            self::SECOND_DAY_AIR_AM,
            self::SECOND_DAY_AIR,
        ];
    }

    public static function economy(): array
    {
        return [
            self::THREE_DAY_SELECT,
            self::GROUND,
        ];
    }

    public static function ground(): array
    {
        return [
            self::GROUND,
        ];
    }

    public static function international(): array
    {
        return [
            self::WORLDWIDE_EXPRESS_PLUS,
            self::WORLDWIDE_EXPRESS,
            self::WORLDWIDE_EXPEDITED,
            self::STANDARD,
            self::SAVER,
            self::WORLDWIDE_ECONOMY_DDU,
            self::WORLDWIDE_ECONOMY_DDP,
        ];
    }

    public static function retail(): array
    {
        return [
            self::ACCESS_POINT,
            self::SUREPOST,
        ];
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return array_map(fn($e) => [
            'code' => $e->value,
            'name' => $e->name,
        ], self::cases());
    }
}
