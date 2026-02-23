<?php

namespace App\Controller\Admin\Order\NewOrder;

class AddOns
{
    const CONFIG = [
        "sides" => [
            "SINGLE" => [
                "key" => "SINGLE",
                "displayText" => "Single Sided",
                "label" => "Choose Your Sides (Single Sided)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "DOUBLE" => [
                "key" => "DOUBLE",
                "displayText" => "Double Sided",
                "label" => "Choose Your Sides (Double Sided)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
        ],
        "grommets" => [
            "NONE" => [
                "key" => "NONE",
                "displayText" => "No Grommets",
                "label" => "Choose Your Grommets (3/8 Inch Hole) (None)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "TOP_CENTER" => [
                "key" => "TOP_CENTER",
                "displayText" => "Grommets (Top Center)",
                "label" => "Choose Your Grommets (3/8 Inch Hole) (Top Center)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "TOP_CORNERS" => [
                "key" => "TOP_CORNERS",
                "displayText" => "Grommets (Top Corners)",
                "label" => "Choose Your Grommets (3/8 Inch Hole) (Top Corners)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "ALL_FOUR_CORNERS" => [
                "key" => "ALL_FOUR_CORNERS",
                "displayText" => "Grommets (Four Corners)",
                "label" => "Choose Your Grommets (3/8 Inch Hole) (Four Corners)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "SIX_CORNERS" => [
                "key" => "SIX_CORNERS",
                "displayText" => "Grommets (Six Corners)",
                "label" => "Choose Your Grommets (3/8 Inch Hole) (Six Corners)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            'CUSTOM_PLACEMENT' => [
                "key" => "CUSTOM_PLACEMENT",
                "displayText" => "Grommets (Custom Placement)",
                "label" => "Choose Your Grommets (3/8 Inch Hole) (Custom Placement)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ]
        ],
        "grommetColor" => [
            "SILVER" => [
                "key" => "SILVER",
                "displayText" => "Silver Grommets",
                "label" => "Choose Grommets Color (Silver)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "BLACK" => [
                "key" => "BLACK",
                "displayText" => "Black Grommets",
                "label" => "Choose Grommets Color (Black)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "GOLD" => [
                "key" => "GOLD",
                "displayText" => "Gold Grommets",
                "label" => "Choose Grommets Color (Gold)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
        ],
        "imprintColor" => [
            "ONE" => [
                "key" => "ONE",
                "displayText" => "1 Imprint Color",
                "label" => "Imprint Color (1 Imprint Colors)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "TWO" => [
                "key" => "TWO",
                "displayText" => "2 Imprint Color",
                "label" => "Imprint Color (2 Imprint Colors)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "THREE" => [
                "key" => "THREE",
                "displayText" => "3 Imprint Color",
                "label" => "Imprint Color (3 Imprint Colors)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "UNLIMITED" => [
                "key" => "UNLIMITED",
                "displayText" => "Unlimited Imprint Color",
                "label" => "Imprint Color (Unlimited Imprint Colors)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
        ],
        "flute" => [
            "VERTICAL" => [
                "key" => "VERTICAL",
                "displayText" => "Vertical Flutes",
                "label" => "Choose Flutes Direction (Vertical)",
                "amount" => 0,
                "type" => "FIXED"
            ],
            "HORIZONTAL" => [
                "key" => "HORIZONTAL",
                "displayText" => "Horizontal Flutes",
                "label" => "Choose Flutes Direction (Horizontal)",
                "amount" => 0,
                "type" => "FIXED"
            ]
        ],
        "frame" => [
            "NONE" => [
                "key" => "NONE",
                "displayText" => "No Frame",
                "label" => "Choose Your Frame (None)",
                "amount" => 0,
                "type" => "FIXED"
            ],
            "WIRE_STAKE_10X24" => [
                "key" => "WIRE_STAKE_10X24",
                "displayText" => "Standard 10\"W X 24\"H Wire Stake Frame",
                "label" => "Choose Your Frame (Standard 10\"W X 30\"H Wire Stake)",
                "amount" => 0,
                "type" => "FIXED"
            ],
            "WIRE_STAKE_10X24_PREMIUM" => [
                "key" => "WIRE_STAKE_10X24_PREMIUM",
                "displayText" => "Premium 10\"W X 24\"H Wire Stake Frame",
                "label" => "Choose Your Frame (Premium 10\"W X 30\"H Wire Stake)",
                "amount" => 0,
                "type" => "FIXED"
            ],
            "WIRE_STAKE_10X30_SINGLE" => [
                "key" => "WIRE_STAKE_10X30_SINGLE",
                "displayText" => "Single 30\"H Wire Stake Frame",
                "label" => "Choose Your Frame (Single 30\"H Wire Stake)",
                "amount" => 0,
                "type" => "FIXED"
            ],
        ],
        "shape" => [
            "SQUARE" => [
                "key" => "SQUARE",
                "displayText" => "Square / Rectangle Shape",
                "label" => "Choose Your Shape (Square Shape)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "CIRCLE" => [
                "key" => "CIRCLE",
                "displayText" => "Circle Shape",
                "label" => "Choose Your Shape (Circle Shape)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "OVAL" => [
                "key" => "OVAL",
                "displayText" => "Oval Shape",
                "label" => "Choose Your Shape (Oval Shape)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "CUSTOM" => [
                "key" => "CUSTOM",
                "displayText" => "Custom Shape",
                "label" => "Choose Your Shape (Custom Shape)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
            "CUSTOM_WITH_BORDER" => [
                "key" => "CUSTOM_WITH_BORDER",
                "displayText" => "Custom with Border Shape",
                "label" => "Choose Your Shape (Custom with Border Shape)",
                "amount" => 0,
                "type" => "PERCENTAGE"
            ],
        ],
    ];

    public static function buildAddOns(array $item): array
    {
        $baseAdOns = [
            "frame"        => AddOns::CONFIG['frame']['NONE'],
            "shape"        => AddOns::CONFIG['shape']['SQUARE'],
            "sides"        => AddOns::CONFIG['sides']['SINGLE'],
            "grommets"     => AddOns::CONFIG['grommets']['NONE'],
            "grommetColor" => AddOns::CONFIG['grommetColor']['SILVER'],
            "imprintColor" => AddOns::CONFIG['imprintColor']['UNLIMITED'],
        ];

        $sidesKey        = is_string($item['sides'] ?? null) ? $item['sides'] : null;
        $shapeKey        = is_string($item['shapes'] ?? null) ? $item['shapes'] : null;
        $imprintColorKey = is_string($item['imprintColor'] ?? null) ? $item['imprintColor'] : null;
        $grommetsKey     = is_string($item['grommets'] ?? null) ? $item['grommets'] : null;
        $grommetColorKey = is_string($item['grommetColor'] ?? null) ? $item['grommetColor'] : null;
        $frameKey        = is_string($item['frame'] ?? null) ? $item['frame'] : null;

        if ($sidesKey && isset(AddOns::CONFIG['sides'][$sidesKey])) {
            $baseAdOns['sides'] = AddOns::CONFIG['sides'][$sidesKey];
        }

        if ($shapeKey && isset(AddOns::CONFIG['shape'][$shapeKey])) {
            $baseAdOns['shape'] = AddOns::CONFIG['shape'][$shapeKey];
        }

        if ($imprintColorKey && isset(AddOns::CONFIG['imprintColor'][$imprintColorKey])) {
            $baseAdOns['imprintColor'] = AddOns::CONFIG['imprintColor'][$imprintColorKey];
        }

        if ($grommetsKey && isset(AddOns::CONFIG['grommets'][$grommetsKey])) {
            $baseAdOns['grommets'] = AddOns::CONFIG['grommets'][$grommetsKey];
        }

        if ($grommetColorKey && isset(AddOns::CONFIG['grommetColor'][$grommetColorKey])) {
            $baseAdOns['grommetColor'] = AddOns::CONFIG['grommetColor'][$grommetColorKey];
        }

        if ($frameKey && isset(AddOns::CONFIG['frame'][$frameKey])) {
            $baseAdOns['frame'] = AddOns::CONFIG['frame'][$frameKey];
        }

        return $baseAdOns;
    }
 
}