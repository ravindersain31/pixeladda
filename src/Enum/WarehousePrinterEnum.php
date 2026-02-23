<?php

namespace App\Enum;

enum WarehousePrinterEnum
{
    const PRINTER_1 = 'P1';

    const PRINTER_2 = 'P2';

    const PRINTER_3 = 'P3';

    const PRINTER_4 = 'P4';

    const PRINTER_5 = 'P5';

    const PRINTER_6 = 'P6';

    const PRINTER_7 = 'P7';

    const PRINTER_8 = 'P8';

    const PRINTER_9 = 'P9';

    const PRINTER_10 = 'P10';

    const UNASSIGNED = 'UNASSIGNED';

    const PRINTERS = [
        self::PRINTER_1 => [
            'label' => 'P1',
            'color' => 'blue',
        ],
        // self::PRINTER_2 => [
        //     'label' => 'P2',
        //     'color' => 'red',
        // ],
        self::PRINTER_3 => [
            'label' => 'P3',
            'color' => '#b9b900',
        ],
        self::PRINTER_4 => [
            'label' => 'P4',
            'color' => '#00921e',
        ],
        self::PRINTER_5 => [
            'label' => 'P5',
            'color' => '#d90e7a',
        ],
        self::PRINTER_6 => [
            'label' => 'P6',
            'color' => '#01adef',
        ],
        self::PRINTER_7 => [
            'label' => 'P7',
            'color' => '#ff6600',
        ],
        self::PRINTER_8 => [
            'label' => 'P8',
            'color' => '#8b4513',
        ],
        self::PRINTER_9 => [
            'label' => 'P9',
            'color' => '#ff1493',
        ],
        self::PRINTER_10 => [
            'label' => 'P10',
            'color' => '#228b22',
        ],
    ];

    public static function getLabel(string $printer, bool $coloredText = false, bool $badge = false): string
    {
        if ($coloredText) {
            $class = $badge ? 'badge badge-light' : '';
            $colorStyle = $badge ? 'background:' : 'color: ';
            return '<span class="' . $class . '" style="' . $colorStyle . self::PRINTERS[$printer]['color'] . '!important">' . self::PRINTERS[$printer]['label'] . '</span>';
        }
        return self::PRINTERS[$printer]['label'];
    }


}