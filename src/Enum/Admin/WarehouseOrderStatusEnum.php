<?php

namespace App\Enum\Admin;

enum WarehouseOrderStatusEnum
{
    const READY = 'READY';

    const NESTING = 'NESTING';

    const NESTED = 'NESTED';

    const PRINTING = 'PRINTING';

    const PAUSED = 'PAUSED';

    const FIXING = 'FIXING';

    const PRINTED = 'PRINTED';

    const CUTTING = 'CUTTING';

    const PACKING = 'PACKING';

    const DONE = 'DONE';

    const STATUS = [
        self::READY => [
            'label' => 'Pending',
            'color' => '#95b502',
        ],
        self::NESTING => [
            'label' => 'Nesting',
            'color' => '#0689ff',
        ],
        self::NESTED => [
            'label' => 'Nested',
            'color' => '#0e00c7',
        ],
        self::PRINTING => [
            'label' => 'Printing',
            'color' => '#970bcc',
        ],
        self::PAUSED => [
            'label' => 'Paused',
            'color' => '#ff7400',
        ],
        self::FIXING => [
            'label' => 'Fixing',
            'color' => '#f90000',
        ],
        self::PRINTED => [
            'label' => 'Printed',
            'color' => '#3ae728',
        ],
        self::CUTTING => [
            'label' => 'Cutting',
            'color' => '#c0b800',
        ],
        self::PACKING => [
            'label' => 'Packing',
            'color' => '#494949',
        ],
        self::DONE => [
            'label' => 'Done',
            'color' => '#085600',
        ],
    ];

    public static function getLabel(string $status, bool $coloredText = false, bool $badge = false): string
    {
        if (!isset(self::STATUS[$status])) {
            return $status;
        }
        if ($coloredText) {
            $class = $badge ? 'badge badge-light' : '';
            $colorStyle = $badge ? 'background:' : 'color: ';
            return '<span class="mx-1 ' . $class . '" style="' . $colorStyle . self::STATUS[$status]['color'] . '!important">' . self::STATUS[$status]['label'] . '</span>';
        }
        return self::STATUS[$status]['label'];
    }
}