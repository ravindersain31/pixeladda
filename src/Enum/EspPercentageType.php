<?php

namespace App\Enum;

enum EspPercentageType: string
{
    case ESP_PERCENTAGE_INCREMENT = 'ESP_PERCENTAGE_INCREMENT';
    case ESP_PERCENTAGE_DECREMENT = 'ESP_PERCENTAGE_DECREMENT';

    public static function getBeforeLoginTypes(): array
    {
        return [
            self::ESP_PERCENTAGE_INCREMENT->value => 'Increment',
            self::ESP_PERCENTAGE_DECREMENT->value => 'Decrement',
        ];
    }

    public static function getAfterLoginTypes(): array
    {
        return [
            self::ESP_PERCENTAGE_INCREMENT->value => 'Increment',
            self::ESP_PERCENTAGE_DECREMENT->value => 'Decrement',
        ];
    }
}