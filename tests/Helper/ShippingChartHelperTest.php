<?php

namespace App\Tests\Helper;

use App\Helper\ShippingChartHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShippingChartHelperTest extends KernelTestCase
{
    private array $shipping = [
        "day_1" => [
            "day" => 1,
            "shipping" => []
        ],
        "day_3" => [
            "day" => 3,
            "shipping" => []
        ],
        "day_5" => [
            "day" => 5,
            "shipping" => []
        ]
    ];

    public function testFullWeekWithWeekendsOnly(): void
    {
        $weekDates = [
            '2023-07-16' => [
                'day_1' => '2023-07-18',
                'day_3' => '2023-07-20',
                'day_5' => '2023-07-24'
            ],
            '2023-07-17' => [
                'day_1' => '2023-07-18',
                'day_3' => '2023-07-20',
                'day_5' => '2023-07-24'
            ],
            '2023-07-18' => [
                'day_1' => '2023-07-19',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-19' => [
                'day_1' => '2023-07-20',
                'day_3' => '2023-07-24',
                'day_5' => '2023-07-26'
            ],
            '2023-07-20' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-21' => [
                'day_1' => '2023-07-24',
                'day_3' => '2023-07-26',
                'day_5' => '2023-07-28'
            ],
            '2023-07-22' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-23' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ]
        ];
        $chartHelper = new ShippingChartHelper();
        $chartHelper->addHolidays([]);
        foreach ($weekDates as $date => $expected) {
            $actual = $chartHelper->basebuild($this->shipping, new \DateTime($date));
            $this->assertEquals($expected['day_1'], $actual['day_1']['date'], 'Day 1 date is wrong');
            $this->assertEquals($expected['day_3'], $actual['day_3']['date'], 'Day 3 date is wrong');
            $this->assertEquals($expected['day_5'], $actual['day_5']['date'], 'Day 5 date is wrong');
        }
    }

    public function testFullWeekWithWeekendsOnlyCutOffTime(): void
    {
        $weekDates = [
            '2023-07-16 17:00:00' => [
                'day_1' => '2023-07-18',
                'day_3' => '2023-07-20',
                'day_5' => '2023-07-24'
            ],
            '2023-07-17 17:00:00' => [
                'day_1' => '2023-07-19',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-18 17:00:00' => [
                'day_1' => '2023-07-20',
                'day_3' => '2023-07-24',
                'day_5' => '2023-07-26'
            ],
            '2023-07-19 17:00:00' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-20 17:00:00' => [
                'day_1' => '2023-07-24',
                'day_3' => '2023-07-26',
                'day_5' => '2023-07-28'
            ],
            '2023-07-21 17:00:00' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-22 17:00:00' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-23 17:00:00' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ]
        ];
        $chartHelper = new ShippingChartHelper();
        $chartHelper->addHolidays([]);
        foreach ($weekDates as $date => $expected) {
            $actual = $chartHelper->basebuild($this->shipping, new \DateTime($date));
            $this->assertEquals($expected['day_1'], $actual['day_1']['date'], 'Day 1 date is wrong');
            $this->assertEquals($expected['day_3'], $actual['day_3']['date'], 'Day 3 date is wrong');
            $this->assertEquals($expected['day_5'], $actual['day_5']['date'], 'Day 5 date is wrong');
        }
    }

    public function testFullWeekWithWeekendsAndIncludingHoliday(): void
    {
        $weekDates = [
            '2023-07-16' => [
                'day_1' => '2023-07-18',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-17' => [
                'day_1' => '2023-07-18',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-18' => [
                'day_1' => '2023-07-20',
                'day_3' => '2023-07-24',
                'day_5' => '2023-07-26'
            ],
            '2023-07-19' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-20' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-21' => [
                'day_1' => '2023-07-24',
                'day_3' => '2023-07-26',
                'day_5' => '2023-07-28'
            ],
            '2023-07-22' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-23' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ]
        ];
        $chartHelper = new ShippingChartHelper();
        $chartHelper->addHolidays([]);
        $chartHelper->addHolidays('2023-07-19');
        foreach ($weekDates as $date => $expected) {
            $actual = $chartHelper->basebuild($this->shipping, new \DateTime($date));
            $this->assertEquals($expected['day_1'], $actual['day_1']['date'], 'Day 1 date is wrong');
            $this->assertEquals($expected['day_3'], $actual['day_3']['date'], 'Day 3 date is wrong');
            $this->assertEquals($expected['day_5'], $actual['day_5']['date'], 'Day 5 date is wrong');
        }
    }


    public function testFullWeekWithWeekendsAndIncludingHolidayCutOffDay(): void
    {
        $weekDates = [
            '2023-07-16 17:00:00' => [
                'day_1' => '2023-07-18',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-17 17:00:00' => [
                'day_1' => '2023-07-20',
                'day_3' => '2023-07-24',
                'day_5' => '2023-07-26'
            ],
            '2023-07-18 17:00:00' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-19 17:00:00' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-20 17:00:00' => [
                'day_1' => '2023-07-24',
                'day_3' => '2023-07-26',
                'day_5' => '2023-07-28'
            ],
            '2023-07-21 17:00:00' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-22 17:00:00' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-23 17:00:00' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ]
        ];
        $chartHelper = new ShippingChartHelper();
        $chartHelper->addHolidays(['2023-07-19']);
        foreach ($weekDates as $date => $expected) {
            $actual = $chartHelper->basebuild($this->shipping, new \DateTime($date));
            $this->assertEquals($expected['day_1'], $actual['day_1']['date'], 'Day 1 date is wrong');
            $this->assertEquals($expected['day_3'], $actual['day_3']['date'], 'Day 3 date is wrong');
            $this->assertEquals($expected['day_5'], $actual['day_5']['date'], 'Day 5 date is wrong');
        }
    }

    public function testFullWeekWithWeekendsAndPushDaysOnly(): void
    {
        $weekDates = [
            '2023-07-16' => [
                'day_1' => '2023-07-19',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-17' => [
                'day_1' => '2023-07-19',
                'day_3' => '2023-07-21',
                'day_5' => '2023-07-25'
            ],
            '2023-07-18' => [
                'day_1' => '2023-07-20',
                'day_3' => '2023-07-24',
                'day_5' => '2023-07-26'
            ],
            '2023-07-19' => [
                'day_1' => '2023-07-21',
                'day_3' => '2023-07-25',
                'day_5' => '2023-07-27'
            ],
            '2023-07-20' => [
                'day_1' => '2023-07-24',
                'day_3' => '2023-07-26',
                'day_5' => '2023-07-28'
            ],
            '2023-07-21' => [
                'day_1' => '2023-07-25',
                'day_3' => '2023-07-27',
                'day_5' => '2023-07-31'
            ],
            '2023-07-22' => [
                'day_1' => '2023-07-26',
                'day_3' => '2023-07-28',
                'day_5' => '2023-08-01'
            ],
            '2023-07-23' => [
                'day_1' => '2023-07-26',
                'day_3' => '2023-07-28',
                'day_5' => '2023-08-01'
            ]
        ];
        $chartHelper = new ShippingChartHelper();
        $chartHelper->addHolidays([]);
        $chartHelper->addPushDays(0);
        $chartHelper->addPushDays(1);
        foreach ($weekDates as $date => $expected) {
            $actual = $chartHelper->basebuild($this->shipping, new \DateTime($date));
            $this->assertEquals($expected['day_1'], $actual['day_1']['date'], 'Day 1 date is wrong');
            $this->assertEquals($expected['day_3'], $actual['day_3']['date'], 'Day 3 date is wrong');
            $this->assertEquals($expected['day_5'], $actual['day_5']['date'], 'Day 5 date is wrong');
        }
    }
}