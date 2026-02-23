<?php

namespace App\Enum;

enum ProductTypeEnum: string
{
    case YARD_SIGN = 'yard-sign';
    case DIE_CUT = 'die-cut';
    case BIG_HEAD_CUTOUTS = 'big-head-cutouts';
    case HAND_FANS = 'hand-fans';
    case YARD_LETTERS = 'yard-letters';
    case BLANK_SIGNS = 'blank-signs';

    public function label(): string
    {
        return match ($this) {
            self::YARD_SIGN => 'Yard Sign',
            self::DIE_CUT => 'Die Cut',
            self::BIG_HEAD_CUTOUTS => 'Big Head Cutouts',
            self::HAND_FANS => 'Hand Fans',
            self::YARD_LETTERS => 'Yard Letters',
            self::BLANK_SIGNS => 'Blank Signs',
        };
    }
}
