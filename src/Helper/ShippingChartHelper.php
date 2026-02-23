<?php

namespace App\Helper;

class ShippingChartHelper
{
    private int $maxQty = 9999999999;
    private array $holidays = [
        "2025-01-01",
        "2025-01-20",
        "2025-04-20",
        "2025-05-11",
        "2025-05-26",
        "2025-07-04",
        "2025-09-01",
        "2025-11-27",
        "2025-12-25",
        "2026-01-01"
    ];


    private int $pushDays = 0;

    private string $cutOffHour = '16:00:00';

    private int $freeShippingDays = 1;

    private int $discountShippingDays = 6;
    private int $discountShippingTwentyDays = 14;

    private bool $freeShippingEnabled = true;

    private bool $isSaturdayDelivery = true;

    public float $minAmountForFreeShipping = 50;
    public float $minAmountForFreeShippingTwentyDay = 100;

    public int $saturdayDay = 0;

    public int $discount = 5;
    public int $discountForTwentyDay = 10;

    public array $quantities = [1, 101, 251, 501, 1001];

    private string $saturdayCutOffHour = '16:00:00';

    private bool $enableSorting = true;

    private bool $saturdayDeliveryRestrictions = false;

    public function basebuild(array $shipping, ?\DateTime $baseDate = null, bool $discount = true, bool $enableDeliveryTiers = false): array
    {
        $baseDate = $baseDate ?? new \DateTime();
        $baseDate = $this->cutOffTime($baseDate);

        $data = [];
        $days = [];
        end($shipping);
        $lastKey = key($shipping);

        if ($this->isSaturdayDelivery) {
            $data += $this->addSaturdayDeliveryOption($data, $shipping, $days, $enableDeliveryTiers, $baseDate);
        }

        foreach ($shipping as $key => $config) {
            if ($enableDeliveryTiers) {
                $day = intval($config['day']) + $this->pushDays + $this->saturdayDay;
            } else {
                $day = intval($config['day']) + $this->saturdayDay;
            }
            $date = $this->addWorkingDays($day - $this->saturdayDay, $baseDate);
            $shipping = $this->getShippingChart($config['shipping'], $days);
            $data['day_' . $day] = [
                'day' => $day,
                'isSaturday' => false,
                'free' => false,
                'discount' => 0,
                'minAmount' => 0,
                'date' => $date->format('Y-m-d'),
                'timestamp' => $date->getTimestamp(),
                'pricing' => $shipping,
            ];

            if ($key === $lastKey && $this->freeShippingEnabled) {
                $freeDay = $day + $this->freeShippingDays;
                $freeDayKey = 'day_' . $freeDay;
                $freeDate = $this->addWorkingDays($freeDay - $this->saturdayDay, $baseDate);
                $data[$freeDayKey] = [
                    'day' => $freeDay,
                    'isSaturday' => false,
                    'free' => true,
                    'discount' => 0,
                    'minAmount' => $this->minAmountForFreeShipping,
                    'date' => $freeDate->format('Y-m-d'),
                    'timestamp' => $freeDate->getTimestamp(),
                    'shipping' => ['qty_1' => [
                        'qty' => [
                            'from' => 1,
                            'to' => $this->maxQty,
                        ]
                    ]],
                ];
            }

            if ($key === $lastKey && $this->freeShippingEnabled && $this->discountShippingDays > 1 && $discount) {
                $freeDay = $day + $this->discountShippingDays;
                $freeDayKey = 'day_' . $freeDay;
                $freeDate = $this->addWorkingDays($freeDay - $this->saturdayDay, $baseDate);
                $data[$freeDayKey] = [
                    'day' => $freeDay,
                    'isSaturday' => false,
                    'free' => true,
                    'discount' => $this->discount,
                    'minAmount' => $this->minAmountForFreeShipping,
                    'date' => $freeDate->format('Y-m-d'),
                    'timestamp' => $freeDate->getTimestamp(),
                    'shipping' => ['qty_1' => [
                        'qty' => [
                            'from' => 1,
                            'to' => $this->maxQty,
                        ]
                    ]],
                ];
            }

            if ($key === $lastKey && $this->freeShippingEnabled && $this->discountShippingTwentyDays > 1 && $discount) {
                $freeDay = $day + $this->discountShippingTwentyDays;
                $freeDayKey = 'day_' . $freeDay;
                $freeDate = $this->addWorkingDays($freeDay - $this->saturdayDay, $baseDate);
                $data[$freeDayKey] = [
                    'day' => $freeDay,
                    'isSaturday' => false,
                    'free' => true,
                    'discount' => $this->discountForTwentyDay,
                    'minAmount' => $this->minAmountForFreeShippingTwentyDay,
                    'date' => $freeDate->format('Y-m-d'),
                    'timestamp' => $freeDate->getTimestamp(),
                    'shipping' => ['qty_1' => [
                        'qty' => [
                            'from' => 1,
                            'to' => $this->maxQty,
                        ]
                    ]],
                ];
            }
        }

        if ($this->enableSorting) {
            usort($data, function ($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });
            $reindexedData = [];
            foreach ($data as $entry) {
                $reindexedData['day_' . $entry['day']] = $entry;
            }
            return $reindexedData;
        }

        return $data;
    }

    public function checkSaturdayDeliveryEligibility(): bool
    {
        $now = new \DateTime('now');
        $currentDay = (int)$now->format('w');
        $currentTime = $now->format('H:i:s');

        return (
            ($currentDay === 4 && $currentTime >= $this->saturdayCutOffHour) || // Thursday after cutoff
            ($currentDay === 5 && $currentTime < $this->saturdayCutOffHour)     // Friday before cutoff
        );
    }

    public function getNextShippingDay(array $shippingChart, \DateTimeInterface $baseDate): ?array
    {
        usort($shippingChart, function ($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        foreach ($shippingChart as $dayData) {
            $dayDate = new \DateTime($dayData['date']);

            if ($dayDate > $baseDate) {
                return $dayData;
            }
        }

        if (!$this->checkSaturdayDeliveryEligibility()) {
            return $this->findClosestNonSaturdayDate($shippingChart, $baseDate);
        }

        return end($shippingChart) ?: null;
    }

    private function addSaturdayDeliveryOption(array $data, array $shipping, array $days, bool $enableDeliveryTiers = false, ?\DateTime $baseDate = null): array
    {
        $firstShippingDay = reset($shipping)['day'] - 1;
        $adjustedBaseDate = (clone $baseDate)->modify("+{$firstShippingDay} days");

        $this->saturdayDay = $this->isSaturdayDelivery ? 1 : 0;
        $saturdayDate = (clone $adjustedBaseDate)->modify('next Saturday');
        $shipping = $this->getShippingChart(reset($shipping)['shipping'], $days);

        if ($enableDeliveryTiers) {
            $day = $this->saturdayDay + $this->pushDays;
        } else {
            $day = $this->saturdayDay;
        }

        $data['day_' . $day] = [
            'day' => $day,
            'isSaturday' => true,
            'free' => false,
            'discount' => 0,
            'minAmount' => 0,
            'date' => $saturdayDate->format('Y-m-d'),
            'timestamp' => $saturdayDate->getTimestamp(),
            'pricing' => $this->doublePricing($shipping),
        ];

        return $data;
    }

    private function doublePricing(array $pricing): array
    {
        return array_map(function ($details) {
            return array_map(function ($value) {
                return is_numeric($value) ? $value * 2 : $value;
            }, $details);
        }, $pricing);
    }

    public function build(array $shipping, ?\DateTime $baseDate = null, bool $discount = true, bool $enableDeliveryTiers = false): array
    {
        $data = [];
        foreach ($this->quantities as $key => $value) {
            if ($enableDeliveryTiers) {
                $this->pushDays = $key;
            }
            $toValue = $this->quantities[$key + 1] ?? $this->maxQty;

            $data['qty_' . $value] = [
                'from' => $value,
                'to' => $toValue,
                'shippingDates' => $this->basebuild($shipping, $baseDate, $discount, $enableDeliveryTiers),
            ];
        }

        return $data;
    }

    public function getShippingByQuantity(int|string $quantity, array $shipping): array
    {
        foreach ($shipping as $key => $value) {
            if ($quantity >= $value['from'] && $quantity < $value['to']) {
                return $value['shippingDates'];
            }
        }
        return $shipping['qty_1']['shippingDates'];
    }

    /**
     * @param array|string $holidays ex: ['2021-01-01', '2021-01-02'] or '2021-01-01'
     * @return $this
     */
    public function addHolidays(array|string $holidays): static
    {
        $holidays = is_array($holidays) ? $holidays : [$holidays];
        $this->holidays += [
            ...$this->holidays,
            ...$holidays,
        ];

        return $this;
    }

    public function addPushDays(int $days): static
    {
        $this->pushDays += $days;

        return $this;
    }

    public function getCutOffHour(): string
    {
        return $this->cutOffHour;
    }

    public function setCutOffHour(string $time): static
    {
        $this->cutOffHour = $time;

        return $this;
    }

    public function getEnableSorting(): bool
    {
        return $this->enableSorting;
    }

    public function setEnableSorting(bool $sort): static
    {
        $this->enableSorting = $sort;

        return $this;
    }

    public function getSaturdayDeliveryRestrictions(): bool
    {
        return $this->saturdayDeliveryRestrictions;
    }

    public function setSaturdayDeliveryRestrictions(bool $restrictions): static
    {
        $this->saturdayDeliveryRestrictions = $restrictions;

        return $this;
    }

    public function getSaturdayCutOffHour(): string
    {
        return $this->saturdayCutOffHour;
    }

    public function setSaturdayCutOffHour(string $time): static
    {
        $this->saturdayCutOffHour = $time;

        return $this;
    }

    public function getSaturdayDelivery(): bool
    {
        return $this->isSaturdayDelivery;
    }

    public function setSaturdayDelivery(bool $isSaturday): static
    {
        $this->isSaturdayDelivery = $isSaturday;

        return $this;
    }

    public function setFreeShippingDays(int $days): static
    {
        $this->freeShippingDays = $days;

        return $this;
    }

    public function setFreeShippingEnabled(bool $enabled): static
    {
        $this->freeShippingEnabled = $enabled;

        return $this;
    }

    public function addWorkingDays($days, $baseDate): \DateTime
    {
        $date = clone $baseDate;
        for ($i = 0; $i < $days; $i++) {
            $date = $date->add(new \DateInterval('P1D'));
            if (in_array($date->format('Y-m-d'), $this->holidays)) {
                $i--;
                continue;
            }

            if ($date->format('N') >= 6) {
                $i--;
            }
        }

        if (in_array($baseDate->format('l'), ['Saturday', 'Sunday'])) {
            $nextMonday = (clone $baseDate)->modify('next monday');
            return $this->addWorkingDays($days, $nextMonday);
        }

        if (in_array($baseDate->format('Y-m-d'), $this->holidays)) {
            $nextMonday = (clone $baseDate)->add(new \DateInterval('P1D'));
            return $this->addWorkingDays($days, $nextMonday);
        }

        return $date;
    }

    private function cutOffTime(\DateTime $date): \DateTime
    {
        if ($date->format('H:i:s') >= $this->cutOffHour) {
            $date->add(new \DateInterval('P1D'));
            $date->setTime(0, 0, 0);
        }
        return $date;
    }

    private function getShippingChart($shipping, &$days): array
    {
        $chart = [];
        foreach ($shipping as $value) {
            $fromQty = intval($value['qty']);
            if (!in_array($fromQty, $days)) {
                $days[] = $fromQty;
            }
            $next = next($shipping) ?? null;
            $chart['qty_' . $fromQty] = [
                'qty' => [
                    'from' => $fromQty,
                    'to' => $next ? intval($next['qty']) - 1 : $this->maxQty,
                ],
            ];
            unset($value['qty']);
            foreach ($value as $currency => $rate) {
                $chart['qty_' . $fromQty][$currency] = floatval($rate);
            }
        }
        return $chart;
    }

    public function calculateShipByDate(\DateTimeInterface $deliveryDate, int $daysInTransit, ?\DateTime $baseDate = null): \DateTime
    {
        $now = $baseDate ?? new \DateTime();
        $cutoffHour = $this->cutOffHour;

        if ((int) $now->format('H') >= $cutoffHour) {
            $now = $this->getNextBusinessDay($now);
        }

        $shipBy = \DateTime::createFromInterface($deliveryDate);
        $businessDaysLeft = $daysInTransit;

        while ($businessDaysLeft > 0) {
            $shipBy->modify('-1 day');
            if ($this->isBusinessDay($shipBy)) {
                $businessDaysLeft--;
            }
        }

        if ($shipBy < $now) {
            // throw new \LogicException('It is too late to ship the order for the selected delivery date.');
        }

        return $shipBy;
    }

    private function isBusinessDay(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N');
        $formatted = $date->format('Y-m-d');

        return $dayOfWeek < 6 && !in_array($formatted, $this->holidays, true);
    }


    private function getNextBusinessDay(\DateTimeInterface $date): \DateTime
    {
        $next = \DateTime::createFromInterface($date);
        do {
            $next->modify('+1 day');
        } while (!$this->isBusinessDay($next));

        return $next;
    }

    public function buildTag(array $shippingChart, array $customerShipping): string
    {
        if (empty($shippingChart) || empty($customerShipping)) {
            return '';
        }

        $shippingDate = $customerShipping['date'] ?? null;
        $shippingDay = $customerShipping['day'] ?? null;
        $isSaturdayDelivery = $customerShipping['isSaturday'] ?? false;

        if ($shippingDate === null || $shippingDay === null) {
            return '';
        }

        $shippingValues = array_values($shippingChart);

        $hasSaturdayDelivery = array_filter($shippingValues, fn($s) => $s['isSaturday'] === true);
        $fastest = $shippingValues[0] ?? null;
        $nextFastest = $shippingValues[1] ?? null;

        if ($hasSaturdayDelivery && !$isSaturdayDelivery && $shippingDay > 1) {
            $shippingDay = $shippingDay - 1;
        }

        $tag = '';
        if ($fastest && $shippingDate === $fastest['date']) {
            $tag = "(Super Rush - {$shippingDay})";
        } elseif ($nextFastest && $shippingDate === $nextFastest['date']) {
            $tag = "(Rush - {$shippingDay})";
        }

        if ($isSaturdayDelivery) {
            $tag .= ' (Saturday Delivery)';
        }

        return $tag;
    }

    private function findClosestNonSaturdayDate(array $shippingChart, \DateTimeInterface $baseDate): ?array
    {
        $closestBefore = null;
        $closestAfter = null;
        
        foreach ($shippingChart as $dayData) {
            $dayDate = new \DateTime($dayData['date']);
            
            if ($dayData['isSaturday']) {
                continue;
            }
            
            if ($dayDate > $baseDate) {
                if ($closestAfter === null || $dayDate < new \DateTime($closestAfter['date'])) {
                    $closestAfter = $dayData;
                }
            }
            
            if ($dayDate <= $baseDate) {
                if ($closestBefore === null || $dayDate > new \DateTime($closestBefore['date'])) {
                    $closestBefore = $dayData;
                }
            }
        }
        
        return $closestAfter ?? $closestBefore;
    }
}
