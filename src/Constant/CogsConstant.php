<?php

namespace App\Constant;

class CogsConstant
{
    public const SHEET_TYPE_SINGLE = 'single';
    public const SHEET_TYPE_FULL = 'full';
    public const SHEET_TYPE_PRE_CUT_24x18 = '24x18';
    public const SHEET_TYPE_PRE_CUT_18x12 = '18x12';

    public const TYPE_WIRE_STAKE_10X30_SINGLE = 'WIRE_STAKE_10X30_SINGLE';
    public const TYPE_WIRE_STAKE_10X30 = 'WIRE_STAKE_10X30';
    public const TYPE_WIRE_STAKE_10X24 = 'WIRE_STAKE_10X24';
    public const TYPE_WIRE_STAKE_10X24_PREMIUM = 'WIRE_STAKE_10X24_PREMIUM';
    public const TYPE_WIRE_STAKE_10X30_PREMIUM = 'WIRE_STAKE_10X30_PREMIUM';

    public const FULL_SHEET_COST = 3.40;
    public const FULL_SHEET_CONTAINER_COST = 3.125;

    public const PRE_CUT_SHEET_24x18_COST = 0.315;
    public const PRE_CUT_SHEET_18x12_COST = 0.157;
    public const PRE_CUT_SHEET_18x24_COST = 0.315;
    public const PRE_CUT_SHEET_24x24_COST = 0.42;
    public const PRE_CUT_CONTAINER_COST = 0.24;

    public const WIRE_STAKE_10X30_SINGLE = 0.09;
    public const WIRE_STAKE_10X30 = 0.16;
    public const WIRE_STAKE_10X30_PREMIUM = 0.31;

    public const WIRE_STAKE_10X24 = 0.14;
    public const WIRE_STAKE_10X24_PREMIUM = 0.31;
    public const STAKES_CONTAINER_COST = 0.15;

    public const INK_COST_SINGLE_SIDED = 0.05;
    public const INK_COST_DOUBLE_SIDED = self::INK_COST_SINGLE_SIDED * 2;

    public const LABOR_COST_SINGLE_SIDED = 0.07;
    public const LABOR_COST_DOUBLE_SIDED = 0.23;

    public const DELIVERY_BOX_COST = 2.00;
    public const BOX_COST = 4;
    public const SIGNS_PER_BOX = 100;

    public static function getStakeCost(string $type): float
    {
        $stakeCosts = [
            self::TYPE_WIRE_STAKE_10X30_SINGLE => self::WIRE_STAKE_10X30_SINGLE,
            self::TYPE_WIRE_STAKE_10X30 => self::WIRE_STAKE_10X30,
            self::TYPE_WIRE_STAKE_10X24 => self::WIRE_STAKE_10X24,
            self::TYPE_WIRE_STAKE_10X30_PREMIUM => self::WIRE_STAKE_10X30_PREMIUM,
            self::TYPE_WIRE_STAKE_10X24_PREMIUM => self::WIRE_STAKE_10X24_PREMIUM,
        ];

        if (!isset($stakeCosts[$type])) {
            throw new \InvalidArgumentException(
                "Invalid stake type: $type. Allowed types: "
                . implode(', ', array_keys($stakeCosts)) . "."
            );
        }

        return $stakeCosts[$type];
    }

    public static function getStakeTypes(): array
    {
        return [
            self::TYPE_WIRE_STAKE_10X30_SINGLE,
            self::TYPE_WIRE_STAKE_10X30,
            self::TYPE_WIRE_STAKE_10X24,
            self::TYPE_WIRE_STAKE_10X30_PREMIUM,
            self::TYPE_WIRE_STAKE_10X24_PREMIUM
        ];
    }
}
