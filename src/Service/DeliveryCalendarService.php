<?php

namespace App\Service;

use App\Entity\ProductType;
use App\Helper\ShippingChartHelper;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryCalendarService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(): array
    {
        $productType = $this->entityManager->getRepository(ProductType::class)->findOneBy(['slug' => 'yard-sign']);
        $shippingPricing = $productType->getShipping();

        $calendar = [];
        $dateTime = new \DateTime();
        for ($i = 0; $i < 15; $i++) {
            $shippingBuilder = new ShippingChartHelper();
            $chart = $shippingBuilder->basebuild($shippingPricing, $dateTime);

            // filter chart
            $isSaturday = array_find($chart, function ($item) {
                return (isset($item['isSaturday']) && $item['isSaturday'] === true);
            }) ?? null;

            $label = $dateTime->format('D M j');
            if ($i == 0) {
                $label = 'Today';
            } elseif ($i == 1) {
                $label = 'Tomorrow';
            }
            $calendar[$dateTime->format('Y-m-d')] = [
                'date' => $dateTime->format('Y-m-d'),
                'label' => $label,
                'chart' => $chart,
                'isSaturday' => $isSaturday ?? null,
            ];

            $dateTime->modify('+1 day');
        }

        return $calendar;
    }

}