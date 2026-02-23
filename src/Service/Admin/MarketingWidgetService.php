<?php

namespace App\Service\Admin;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Entity\StoreSettings;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MarketingWidgetService
{

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function get(string $widgetName): float
    {
        $widgetList = $this->dashboardWidgetList();
        $widget = $widgetList['orders']['widgets'][$widgetName] ??
        $widgetList['finance']['widgets'][$widgetName] ??
        $widgetList['finance']['widgets']['Sales']['subTitles'][$widgetName] ??
        null;
        if (!$widget) {
            return 0;
        }

        if (isset($widget['query']) && $widget['query'] instanceof Query) {
            try {
                return $widget['query']->getSingleScalarResult() ?? 0;
            } catch (\Exception $e) {
                return 0;
            }
        }else if(isset($widget['result'])){
            return $widget['result'] ?? 0;
        }

        return 0;
    }

    public function dashboardWidgetList(): array
    {
        $todayDate = [
            'start' => (new \DateTimeImmutable())->setTime(0, 0, 0),
            'end' => (new \DateTimeImmutable())->setTime(23, 59, 59),
        ];
        $yesterdayDate = [
            'start' => (new \DateTimeImmutable())->modify('-1 day')->setTime(0, 0, 0),
            'end' => (new \DateTimeImmutable())->modify('-1 day')->setTime(23, 59, 59),
        ];

        $lastWeekDate = [
            'start' => (new \DateTimeImmutable())->modify('-7 days')->setTime(0, 0, 0),
            'end' => (new \DateTimeImmutable())->setTime(23, 59, 59),
        ];

        $orders = [
            'TodaySales' => [
                'title' => 'Today Sales',
                'currency' => '$',
                'icon' => 'fa fa-dollar-sign',
                'class' => 'border-left-5 border-green text-green',
                'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::VOIDED, PaymentStatusEnum::PENDING],
                    onlyAmount: true,
                ),
                'url' => '#',
            ],
            'TodayOrders' => [
                'title' => 'Today Orders',
                'icon' => 'fa fa-shopping-cart',
                'class' => 'border-left-5 border-orange text-orange',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    onlyCount: true,
                ),
                'url' => '#',
            ],
            'TodayAverageOrderPrice' => [
                'title' => 'Avg. Order Price',
                'currency' => '$',
                'icon' => 'fa fa-tag',
                'class' => 'border-left-5 border-purple text-purple',
                'result' => $this->entityManager->getRepository(OrderTransaction::class)->getAverageOrderPrice(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                ),
            ],
            'TodayPaidSales' => [
                'title' => 'Today Paid Sales',
                'currency' => '$',
                'icon' => 'fa fa-money-bill-wave',
                'class' => 'border-left-5 border-blue text-blue',
                'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                    receivedAmount: true,
                    onlyAmount: true,
                ),
            ],
        ];

        $finance = [
            'TotalSales' => [
                'title' => 'Total Sales',
                'currency' => '$',
                'icon' => 'fa fa-dollar-sign',
                'class' => 'border-left-5 border-green text-green',
                'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                    fromDate: $lastWeekDate['start'],
                    endDate: $lastWeekDate['end'],
                    status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::VOIDED, PaymentStatusEnum::PENDING],
                    onlyAmount: true,
                ),
            ],
            'TotalOrders' => [
                'title' => 'Total Orders',
                'icon' => 'fa fa-shopping-cart',
                'class' => 'border-left-5 border-orange text-orange',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $lastWeekDate['start'],
                    endDate: $lastWeekDate['end'],
                    onlyCount: true,
                ),
            ],
            'TotalAverageOrderPrice' => [
                'title' => 'Avg. Order Price',
                'currency' => '$',
                'icon' => 'fa fa-tag',
                'class' => 'border-left-5 border-purple text-purple',
                'result' => $this->entityManager->getRepository(OrderTransaction::class)->getAverageOrderPrice(
                    fromDate: $lastWeekDate['start'],
                    endDate: $lastWeekDate['end'],
                ),
            ],
            'TotalPaidSales' => [
                'title' => 'Paid Sales',
                'currency' => '$',
                'icon' => 'fa fa-money-bill-wave',
                'class' => 'border-left-5 border-blue text-blue',
                'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                    fromDate: $lastWeekDate['start'],
                    endDate: $lastWeekDate['end'],
                    status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                    receivedAmount: true,
                    onlyAmount: true,
                ),
            ],
        ];

        return [
            'orders' => [
                'title' => 'Today Orders Statistics',
                'icon' => 'fa fa-shopping-cart',
                'widgets' => $orders
            ],
            'finance' => [
                'title' => 'Last 7 Days Sales Report',
                'icon' => 'fa fa-shopping-cart',
                'widgets' => $finance
            ]
        ];
    }
}