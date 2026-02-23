<?php

namespace App\Enum;

enum ProductEnum: string
{
    case BLANK_SIGN = 'BLANK-SIGN';
    case WIRE_STAKE = 'WIRE-STAKE';
    case SAMPLE = 'SAMPLE';
    case BIG_HEAD_CUT_OUT = 'BHC-CUSTOM';
    case HF_CUSTOM = 'HF-CUSTOM';
    case DC_CUSTOM = 'DC-CUSTOM';
    case CUSTOM = 'CUSTOM';

    public function label(): string
    {
        return match ($this) {
            self::BLANK_SIGN => 'Blank Sign',
            self::WIRE_STAKE => 'Wire Stake',
            self::SAMPLE => 'Sample',
            self::BIG_HEAD_CUT_OUT => 'Big Head Cut Out',
            self::HF_CUSTOM => 'HF Custom',
            self::DC_CUSTOM => 'DC Custom',
            self::CUSTOM => 'Custom',
        };
    }
}
