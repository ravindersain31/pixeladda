<?php

namespace App\Constant\Editor;

class Addons
{

    const FLUTE = 'FLUTE';
    const FLUTE_VERTICAL = 'VERTICAL';
    const FLUTE_HORIZONTAL = 'HORIZONTAL';

    // Frame self
    const FRAME = 'FRAME';
    const FRAME_NONE = 'NONE';
    const FRAME_WIRE_STAKE_10X30 = 'WIRE_STAKE_10X30';
    const FRAME_WIRE_STAKE_10X30_PREMIUM = 'WIRE_STAKE_10X30_PREMIUM';
    const FRAME_WIRE_STAKE_10X30_SINGLE = 'WIRE_STAKE_10X30_SINGLE';
    const FRAME_WIRE_STAKE_10X24 = 'WIRE_STAKE_10X24';
    const FRAME_WIRE_STAKE_10X24_PREMIUM = 'WIRE_STAKE_10X24_PREMIUM';

    public const FRAME_PERCENTAGE = 'PERCENTAGE';
    public const FRAME_FIXED = 'FIXED';

    // Sides self
    public const SIDES = 'SIDES';
    public const SIDES_SINGLE = 'SINGLE';
    public const SIDES_DOUBLE = 'DOUBLE';

    // ImprintColor self
    public const IMPRINT_COLOR = 'IMPRINT_COLOR';
    public const IMPRINT_COLOR_ONE = 'ONE';
    public const IMPRINT_COLOR_TWO = 'TWO';
    public const IMPRINT_COLOR_THREE = 'THREE';
    public const IMPRINT_COLOR_UNLIMITED = 'UNLIMITED';

    // Grommets self
    public const GROMMETS = 'GROMMETS';
    public const GROMMETS_NONE = 'NONE';
    public const GROMMETS_TOP_CENTER = 'TOP_CENTER';
    public const GROMMETS_TOP_CORNERS = 'TOP_CORNERS';
    public const GROMMETS_FOUR_CORNERS = 'ALL_FOUR_CORNERS';
    public const GROMMETS_SIX_CORNERS = 'SIX_CORNERS';
    public const CUSTOM_PLACEMENT = 'CUSTOM_PLACEMENT';

    // GrommetColor self
    public const GROMMET_COLOR = 'GROMMET_COLOR';
    public const GROMMET_COLOR_SILVER = 'SILVER';
    public const GROMMET_COLOR_BLACK = 'BLACK';
    public const GROMMET_COLOR_GOLD = 'GOLD';

    // Shape self
    public const SHAPE = 'SHAPE';
    public const SHAPE_SQUARE = 'SQUARE';
    public const SHAPE_CIRCLE = 'CIRCLE';
    public const SHAPE_OVAL = 'OVAL';
    public const SHAPE_CUSTOM = 'CUSTOM';
    public const SHAPE_CUSTOM_WITH_BORDER = 'CUSTOM_WITH_BORDER';


    public function AddOnPrices(): array
    {
        $addOnPrices = [
            self::FLUTE => [
                self::FLUTE_VERTICAL => 0,
                self::FLUTE_HORIZONTAL => 0,
            ],
            self::FRAME => [
                self::FRAME_NONE => 0,
                self::FRAME_WIRE_STAKE_10X30 => 1.79,
                self::FRAME_WIRE_STAKE_10X24 => 1.79,
                self::FRAME_WIRE_STAKE_10X30_PREMIUM => 1.79 * 2,
                self::FRAME_WIRE_STAKE_10X24_PREMIUM => 1.79 * 2,
                self::FRAME_WIRE_STAKE_10X30_SINGLE => 1.79 / 2,
                self::FRAME_PERCENTAGE => 0,
                self::FRAME_FIXED => 0,
            ],
            self::SIDES => [
                self::SIDES_SINGLE => 0,
                self::SIDES_DOUBLE => 30,
            ],
            self::GROMMETS => [
                self::GROMMETS_NONE => 0,
                self::GROMMETS_TOP_CENTER => 10,
                self::GROMMETS_TOP_CORNERS => 15,
                self::GROMMETS_FOUR_CORNERS => 20,
                self::GROMMETS_SIX_CORNERS => 25,
                self::CUSTOM_PLACEMENT => 30
            ],
            self::GROMMET_COLOR => [
                self::GROMMET_COLOR_SILVER => 0,
                self::GROMMET_COLOR_BLACK => 10,
                self::GROMMET_COLOR_GOLD => 5,
            ],
            self::IMPRINT_COLOR => [
                self::IMPRINT_COLOR_ONE => 0,
                self::IMPRINT_COLOR_TWO => 10,
                self::IMPRINT_COLOR_THREE => 20,
                self::IMPRINT_COLOR_UNLIMITED => 30,
            ],
            self::SHAPE => [
                self::SHAPE_SQUARE => 0,
                self::SHAPE_CIRCLE => 10,
                self::SHAPE_OVAL => 20,
                self::SHAPE_CUSTOM => 30,
                self::SHAPE_CUSTOM_WITH_BORDER => 35
            ]
        ];

        return $addOnPrices;
    }

    public function getAddOnPricesByKey(string $key): array
    {
        $addOnPrices = $this->AddOnPrices();

        $typeKey = $this->mapKeyToType($key);

        if (!array_key_exists($typeKey, $addOnPrices)) {
            throw new \InvalidArgumentException("Invalid key provided: $key");
        }

        return $addOnPrices[$typeKey];
    }

    public static function hasSubAddon($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }

    private function mapKeyToType(string $key): string
    {
        $mapping = [
            'flute' => self::FLUTE,
            'frame' => self::FRAME,
            'sides' => self::SIDES,
            'grommetColor' => self::GROMMET_COLOR,
            'grommetsColor' => self::GROMMET_COLOR,
            'grommets' => self::GROMMETS,
            'imprintColor' => self::IMPRINT_COLOR,
            'shape' => self::SHAPE,
        ];

        return $mapping[$key] ?? throw new \InvalidArgumentException("Invalid key mapping: $key");
    }

    public static function getFrameTypes(): array
    {
        return [
            self::FRAME_NONE,
            self::FRAME_WIRE_STAKE_10X30,
            self::FRAME_WIRE_STAKE_10X30_PREMIUM,
            self::FRAME_WIRE_STAKE_10X30_SINGLE,
            self::FRAME_WIRE_STAKE_10X24,
            self::FRAME_WIRE_STAKE_10X24_PREMIUM
        ];
    }

    public static function getFrameTypeLabel(string $frameType): string
    {
        $labels = array_combine(self::getFrameTypes(), self::getFrameTypes());

        return $labels[$frameType] ?? self::FRAME_NONE;
    }

    public static function filterFrameTypeLabel(string $frameType): string
    {
        $labels = array_combine(self::getFrameTypes(), self::getFrameTypes());

        return $labels[$frameType] ?? self::FRAME_WIRE_STAKE_10X30;
    }

    public static function getFrameTypePrice(float $originalPrice, string $frameType): float
    {
        $multiplier = self::getFrameMultiplier($frameType);
        return (float) number_format($originalPrice * $multiplier, 2, '.', '');
    }

    public static function getFrameMultiplier(string $frameType): float
    {
        switch ($frameType) {
            case self::FRAME_WIRE_STAKE_10X30:
            case self::FRAME_WIRE_STAKE_10X24:
                return 1;
            case self::FRAME_WIRE_STAKE_10X30_PREMIUM:
            case self::FRAME_WIRE_STAKE_10X24_PREMIUM:
                return 2;
            case self::FRAME_WIRE_STAKE_10X30_SINGLE:
                return 0.5;
            default:
                return 0;
        }
    }

    public static function getFrameQuantityType(string $frameType): string
    {
        switch ($frameType) {
            case self::FRAME_WIRE_STAKE_10X30:
            case self::FRAME_WIRE_STAKE_10X24:
                return 'Standard';
            case self::FRAME_WIRE_STAKE_10X30_PREMIUM:
            case self::FRAME_WIRE_STAKE_10X24_PREMIUM:
                return 'Premium';
            case self::FRAME_WIRE_STAKE_10X30_SINGLE:
                return 'Single';
            case self::FRAME_NONE:
                return 'None';
            default:
                return 'None';
        }
    }

    public static function getFrameDisplayText(string $frameType): string
    {
        switch ($frameType) {
            case self::FRAME_WIRE_STAKE_10X30:
                return 'Standard 10"W x 30"H';
            case self::FRAME_WIRE_STAKE_10X30_PREMIUM:
                return 'Premium 10"W x 30"H';
            case self::FRAME_WIRE_STAKE_10X30_SINGLE:
                return 'Single 30"H';
            case self::FRAME_NONE:
                return 'None';
            case self::FRAME_WIRE_STAKE_10X24:
                return 'Standard 10"W x 24"H';
            case self::FRAME_WIRE_STAKE_10X24_PREMIUM:
                return 'Premium 10"W x 24"H';
            default:
                return 'None';
        }
    }

    public function ChooseYourSides(): array
    {
        return [
            self::SIDES_SINGLE => self::SIDES_SINGLE,
            self::SIDES_DOUBLE => self::SIDES_DOUBLE,
        ];
    }


    public function getSidesData($side): array
    {
        $sidesData = [
            self::SIDES_SINGLE => [
                "amount" => self::AddOnPrices()[self::SIDES][self::SIDES_SINGLE],
                "type" => "FIXED",
                "key" => self::SIDES_SINGLE,
                "displayText" => "Single Sided",
                "displayAmount" => 'FREE',
                "label" => "Choose Your Sides (Single Sided)",
                "img" => "https://static.yardsignplus.com/assets/side-option-front.png"
            ],
            self::SIDES_DOUBLE => [
                "amount" => 1,
                "type" => "FIXED",
                "key" => self::SIDES_DOUBLE,
                "displayText" => "Double Sided",
                "displayAmount" => '+$1',
                "label" => "Choose Your Sides (Double Sided)",
                "img" => "https://static.yardsignplus.com/assets/side-option-front-back.png"
            ],
        ];

        return $sidesData[$side] ?? [];
    }

    public function getSampleShapesData($shape): array
    {
        $sampleShapesData = [
            self::SHAPE_SQUARE => [
                "amount" => self::AddOnPrices()[self::SHAPE][self::SHAPE_SQUARE],
                "type" => "FIXED",
                "key" => self::SHAPE_SQUARE,
                "displayText" => "Square / Rectangle",
                "displayAmount" => 'FREE',
                "label" => "Choose Your Shape (Square Shape)",
                "img" => "https://static.yardsignplus.com/assets/Square.png"
            ],
            self::SHAPE_CUSTOM => [
                "amount" => 1,
                "type" => "FIXED",
                "key" => self::SHAPE_CUSTOM,
                "displayText" => "Custom",
                "displayAmount" => '+$1',
                "label" => "Choose Your Shape (Custom Shape)",
                "img" => "https://static.yardsignplus.com/assets/Custom.png"
            ],
        ];

        return $sampleShapesData[$shape] ?? [];
    }

    public function getSampleShapes(): array
    {
        return [
            self::SHAPE_SQUARE => self::SHAPE_SQUARE,
            self::SHAPE_CUSTOM => self::SHAPE_CUSTOM,
        ];
    }

    public static function getShapes(): array
    {
        return [
            self::SHAPE_SQUARE,
            self::SHAPE_CIRCLE,
            self::SHAPE_OVAL,
            self::SHAPE_CUSTOM,
            self::SHAPE_CUSTOM_WITH_BORDER,
        ];
    }

    public static function getImprintColors(): array
    {
        return [
            self::IMPRINT_COLOR_ONE,
            self::IMPRINT_COLOR_TWO,
            self::IMPRINT_COLOR_THREE,
            self::IMPRINT_COLOR_UNLIMITED
        ];
    }

    public static function getFlute(): array
    {
        return [
            self::FLUTE_VERTICAL,
            self::FLUTE_HORIZONTAL
        ];
    }
}
