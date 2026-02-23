<?php

namespace App\Service\Admin;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Entity\Reports\DailyCogsReport;
use App\Entity\StoreSettings;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\WarehousePrinterEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DashboardWidgetService
{

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator, private readonly EntityManagerInterface $entityManager) {}

    public function get(string $widgetName): float
    {
        $widgetList = $this->dashboardWidgetList();
        $widget = $widgetList['orders']['widgets'][$widgetName] ??
            $widgetList['finance']['widgets'][$widgetName] ??
            $widgetList['finance']['widgets']['Sales']['subTitles'][$widgetName] ??
            $widgetList['finance']['widgets']['TodayAdsCost']['subTitles'][$widgetName] ??
            $widgetList['finance']['widgets']['YesterdayAdsCost']['subTitles'][$widgetName] ??
            $widgetList['finance']['widgets']['TodayPaidSales']['subTitles'][$widgetName] ??
            $widgetList['finance']['widgets']['TotalDaysBehind']['table']['rows'][$widgetName] ??
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
        } else if (isset($widget['result'])) {
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
        $orders = [
            'TodayPaidOrders' => [
                'title' => 'Today Paid Orders',
                'icon' => 'fa fa-shopping-cart',
                'class' => 'border-left-5 border-blue text-blue',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    paymentStatus: PaymentStatusEnum::COMPLETED,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['fromDate' => $todayDate['start']->format('m/d/Y'), 'endDate' => $todayDate['end']->format('m/d/Y')]),
            ],
            'TodayCheckOrders' => [
                'title' => 'Today Check Orders',
                'icon' => 'fa fa-money-check-dollar',
                'class' => 'border-left-5 border-purple text-purple',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    paymentStatus: PaymentStatusEnum::PENDING,
                    paymentMethod: PaymentMethodEnum::CHECK,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', [
                    'paymentStatus' => PaymentStatusEnum::PENDING,
                    'paymentMethod' => PaymentMethodEnum::CHECK,
                    'fromDate' => $todayDate['start']->format('m/d/Y'),
                    'endDate' => $todayDate['end']->format('m/d/Y')
                ]),
            ],
            'TodaySeeDesignPayLaterOrders' => [
                'title' => 'Today See Design Pay Later',
                'icon' => 'fa fa-clock',
                'class' => 'border-left-5 border-info text-info',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    paymentStatus: PaymentStatusEnum::PENDING,
                    paymentMethod: PaymentMethodEnum::SEE_DESIGN_PAY_LATER,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', [
                    'paymentStatus' => PaymentStatusEnum::PENDING,
                    'paymentMethod' => PaymentMethodEnum::SEE_DESIGN_PAY_LATER,
                    'fromDate' => $todayDate['start']->format('m/d/Y'),
                    'endDate' => $todayDate['end']->format('m/d/Y')
                ]),
            ],
            'TodayPendingOrders' => [
                'title' => 'Today Pending Orders',
                'icon' => 'fa fa-database',
                'class' => 'border-left-5 border-warning text-warning',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    paymentStatus: PaymentStatusEnum::PENDING,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', [
                    'paymentStatus' => PaymentStatusEnum::PENDING,
                    'fromDate' => $todayDate['start']->format('m/d/Y'),
                    'endDate' => $todayDate['end']->format('m/d/Y')
                ]),
            ],
            'YesterdayAllOrders' => [
                'title' => 'Yesterday All Orders',
                'icon' => 'fa fa-shopping-cart',
                'class' => 'border-left-5 border-dark text-dark',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $yesterdayDate['start'],
                    endDate: $yesterdayDate['end'],
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', [
                    'fromDate' => $yesterdayDate['start']->format('m/d/Y'),
                    'endDate' => $yesterdayDate['end']->format('m/d/Y')
                ]),
            ],
            'UploadProofs' => [
                'title' => 'Upload Proofs',
                'icon' => 'fa fa-file-arrow-up',
                'class' => 'border-left-5 border-secondary text-blue',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    status: [OrderStatusEnum::RECEIVED, OrderStatusEnum::CHANGES_REQUESTED],
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'upload-proof']),
            ],
            'WorkingOnProofs' => [
                'title' => 'Working On Proofs',
                'icon' => 'fa fa-user-pen',
                'class' => 'border-left-5 border-purple text-purple',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    status: [OrderStatusEnum::DESIGNER_ASSIGNED],
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'designer-assigned']),
            ],
            'WaitingOnCustomer' => [
                'title' => 'Waiting On Customer',
                'icon' => 'fa-brands fa-watchman-monitoring',
                'class' => 'border-left-5 border-info text-info',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    status: [OrderStatusEnum::PROOF_UPLOADED],
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'proof-uploaded']),
            ],
            'ApprovedProofs' => [
                'title' => 'Approved Proofs',
                'icon' => 'fa fa-thumbs-up',
                'class' => 'border-left-5 border-dark text-dark',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    status: [OrderStatusEnum::PROOF_APPROVED],
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'proof-approved']),
            ],
            'CheckPOOrders' => [
                'title' => 'Check PO',
                'icon' => 'fa fa-money-check-alt',
                'class' => 'border-left-5 border-warning text-warning',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    paymentMethod: PaymentMethodEnum::CHECK,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'check-po']),
            ],
            'TodaySuperRushOrders' => [
                'title' => 'Today Super Rush Orders',
                'icon' => 'fa fa-truck-fast',
                'class' => 'border-left-5 border-dark text-dark',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    isSuperRush: true,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'today-super-rush-order']),
            ],
            'TodayOrderProtectionOrders' => [
                'title' => 'Today Order Protection Orders',
                'icon' => 'fa fa-lock',
                'class' => 'border-left-5 border-dark text-dark',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    isOrderProtection: true,
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'order-protection-orders']),
            ],
            'TodayOrderProtectionAmount' => [
                'title' => 'Today Order Protection Amount',
                'icon' => 'fa fa-lock',
                'currency' => '$',
                'class' => 'border-left-5 border-orange text-orange',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    isOrderProtection: true,
                    onlyAmount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'order-protection-orders']),
            ],
            'TodayRepeatOrders' => [
                'title' => 'Today Repeat Order',
                'icon' => 'fa fa-award',
                'class' => 'border-left-5 border-purple text-purple',
                'result' => $this->entityManager->getRepository(Order::class)->findOrdersGroupedByUser(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    onlyCount: true,
                ),
                'url' => $this->urlGenerator->generate('admin_orders', ['status' => 'repeat-orders']),
            ],
            'TotalUnmadeSigns' => [
                'title' => 'Total Unmade Signs',
                'icon' => 'fa fa-award',
                'class' => 'border-left-5 border-purple text-purple',
                'result' => $this->entityManager->getRepository(Order::class)->getTotalQuantityOfOrders(
                    status: [OrderStatusEnum::PROOF_APPROVED, OrderStatusEnum::SENT_FOR_PRODUCTION],
                ),
            ],
            'DaysRequired' => [
                'title' => 'Total Days Req. Unmade Signs',
                'icon' => 'fa fa-clock',
                'class' => 'border-left-5 border-purple text-purple',
                'result' => $this->entityManager->getRepository(StoreSettings::class)->getTotalDaysRequiredUnmadeSigns(
                    status: [OrderStatusEnum::PROOF_APPROVED, OrderStatusEnum::SENT_FOR_PRODUCTION],
                ),
            ],
            'DailyCapacity' => [
                'title' => 'Daily Capacity',
                'icon' => 'fa fa-truck-fast',
                'class' => 'border-left-5 border-purple text-purple',
                'result' => $this->entityManager->getRepository(StoreSettings::class)->getDailyCapacity(),
            ],
            'TotalDaysBehind' => [
                'title' => 'Total Days Behind',
                'class' => 'border-left-5 border-purple text-purple',
                'table' => [
                    'headers' => ['Printer' => 1, 'Signs' => 1, 'Time Behind' => 2],
                    'rows' => array_map(function (string $printer) use ($todayDate) {
                        $orders = $this->entityManager->getRepository(WarehouseOrder::class)->findQueue(
                            printerName: $printer,
                            exceptShipByDate: $todayDate['start'],
                        )->getResult();

                        return [
                            'printer' => $printer,
                            'total_signs' => $this->calculateTotalSigns($orders),
                            'total_time_behind' => $this->calculateTotalTimeBehind($orders, $printer),
                        ];
                    }, array_keys(WarehousePrinterEnum::PRINTERS)),
                ],
            ],
        ];
        $finance = [
            'Sales' => [
                'title' => 'Today Sales',
                'subTitles' => [
                    'TodaySales' => [
                        'title' => 'Today Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            onlyAmount: true,
                        ),
                    ],
                    'TodayPaidSales' => [
                        'title' => 'Today Paid Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            onlyReceivedAmount: true,
                            onlyAmount: true,
                        ),
                    ],
                    'AvarageOrderPrice' => [
                        'title' => 'Avg. Order Price',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(OrderTransaction::class)->getAverageOrderPrice(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                        ),
                    ],
                    'TotalOrders' => [
                        'title' => 'Total Orders',
                        'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            onlyCount: true,
                        ),
                    ],
                ],
                'icon' => 'fa fa-dollar',
                'class' => 'border-left-5 border-blue text-blue',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    onlyAmount: true,
                ),
            ],
            'YesterdaySales' => [
                'title' => 'Yesterday Sales',
                'currency' => '$',
                'icon' => 'fa fa-dollar',
                'class' => 'border-left-5 border-dark text-dark',
                'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                    fromDate: $yesterdayDate['start'],
                    endDate: $yesterdayDate['end'],
                    onlyAmount: true,
                ),
            ],
            'TodayAdsCost' => [
                'title' => 'Today Ads Cost',
                'subTitles' => [
                    'TodayBingCost' => [
                        'title' => 'Bing Ads',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate(
                            date: $todayDate['start'],
                            platform: 'bing'
                        ),
                    ],
                    'TodayGoogleCost' => [
                        'title' => 'Google Ads',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate(
                            date: $todayDate['start'],
                            platform: 'google'
                        ),
                    ],
                    'TodayFacebookCost' => [
                        'title' => 'Facebook Ads',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate(
                            date: $todayDate['start'],
                            platform: 'facebook'
                        ),
                    ],
                ],
                'icon' => 'fa fa-dollar',
                'class' => 'border-left-5 border-purple text-blue',
                'result' => null,
            ],
            'YesterdayAdsCost' => [
                'title' => 'Yesterday Ads Cost',
                'subTitles' => [
                    'YesterdayBingCost' => [
                        'title' => 'Bing Ads',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate(
                            date: $yesterdayDate['start'],
                            platform: 'bing'
                        ),
                    ],
                    'YesterdayGoogleCost' => [
                        'title' => 'Google Ads',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate(
                            date: $yesterdayDate['start'],
                            platform: 'google'
                        ),
                    ],
                    'YesterdayFacebookCost' => [
                        'title' => 'Facebook Ads',
                        'currency' => '$',
                        'result' => $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate(
                            date: $yesterdayDate['start'],
                            platform: 'facebook'
                        ),
                    ],
                ],
                'icon' => 'fa fa-dollar',
                'class' => 'border-left-5 border-orange text-blue',
                'result' => null,
            ],
            'TodayPaidSales' => [
                'title' => 'Today Paid Sales',
                'currency' => '$',
                'icon' => 'fa fa-dollar',
                'class' => 'border-left-5 border-purple text-blue',
                'subTitles' => [
                    'TotalPaidSales' => [
                        'title' => 'Total Paid Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(Order::class)->filterOrder(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            onlyReceivedAmount: true,
                            onlyAmount: true,
                        ),
                    ],
                    'BraintreePaidSales' => [
                        'title' => 'Braintree Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                            paymentMethod: PaymentMethodEnum::CREDIT_CARD,
                            receivedAmount: true,
                            onlyAmount: true,
                        ),
                    ],
                    'PayPalPaidSales' => [
                        'title' => 'PayPal Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                            paymentMethod: [PaymentMethodEnum::PAYPAL, PaymentMethodEnum::PAYPAL_EXPRESS],
                            receivedAmount: true,
                            onlyAmount: true,
                        ),
                    ],
                    'CheckPaidSales' => [
                        'title' => 'Check/PO Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                            paymentMethod: PaymentMethodEnum::CHECK,
                            receivedAmount: true,
                            onlyAmount: true,
                        ),
                    ],
                    'GooglePayPaidSales' => [
                        'title' => 'GooglePay Sales',
                        'currency' => '$',
                        'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                            fromDate: $todayDate['start'],
                            endDate: $todayDate['end'],
                            status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                            paymentMethod: PaymentMethodEnum::GOOGLE_PAY,
                            receivedAmount: true,
                            onlyAmount: true,
                        ),
                    ],
                ],
                'query' => $this->entityManager->getRepository(OrderTransaction::class)->filterSales(
                    fromDate: $todayDate['start'],
                    endDate: $todayDate['end'],
                    status: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
                    receivedAmount: true,
                    onlyAmount: true,
                ),
            ],

        ];
        return [
            'orders' => [
                'title' => 'Orders Statistics',
                'icon' => 'fa fa-shopping-cart',
                'widgets' => $orders
            ],
            'finance' => [
                'title' => 'Sales Reports',
                'icon' => 'fa fa-shopping-cart',
                'widgets' => $finance
            ]
        ];
    }

    public function getLast7DaysData(): array
    {
        $data = [];
        $today = new \DateTimeImmutable();
        $endOfToday = $today->setTime(23, 59, 59);

        for ($i = 0; $i < 7; $i++) {
            $endOfDay = $endOfToday->modify("-{$i} day");
            $startOfDay = $endOfDay->setTime(0, 0, 0);

            $sales = $this->entityManager->getRepository(Order::class)->filterOrder(
                fromDate: $startOfDay,
                endDate: $endOfDay,
                onlyAmount: true,
            )->getSingleScalarResult() ?? 0;

            $totalOrders = $this->entityManager->getRepository(Order::class)->filterOrder(
                fromDate: $startOfDay,
                endDate: $endOfDay,
                onlyCount: true,
            )->getSingleScalarResult() ?? 0;

            $avgOrderPrice = $this->entityManager->getRepository(OrderTransaction::class)->getAverageOrderPrice(
                fromDate: $startOfDay,
                endDate: $endOfDay,
            );

            $paidSales = $this->entityManager->getRepository(Order::class)->filterOrder(
                fromDate: $startOfDay,
                endDate: $endOfDay,
                onlyReceivedAmount: true,
                onlyAmount: true,
            )->getSingleScalarResult() ?? 0;

            $googleAdsCost = $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate($endOfDay, 'google');
            $facebookAdsCost = $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate($endOfDay, 'facebook');
            $bingAdsCost = $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate($endOfDay, 'bing');
            $adsCost = $this->entityManager->getRepository(DailyCogsReport::class)->getAdsCostByDate($endOfDay, 'all');
            $roas = $adsCost > 0 ? $paidSales / $adsCost : 0;

            $data[] = [
                'date' => $endOfDay->format('Y-m-d'),
                'sales' => $sales,
                'total_orders' => $totalOrders,
                'avg_order_price' => round($avgOrderPrice, 2),
                'paid_sales' => $paidSales,
                'google_ads_cost' => $googleAdsCost,
                'facebook_ads_cost' => $facebookAdsCost,
                'bing_ads_cost' => $bingAdsCost,
                'total_ads_cost' => $adsCost,
                'roas' => round($roas, 2),
            ];
        }

        return $data;
    }

    public function calculateTotalSigns(array $warehouseOrders): int
    {
        $totalSigns = 0;

        foreach ($warehouseOrders as $warehouseOrder) {
            $order = $warehouseOrder->getOrder();
            $totalQuantities = $order->getTotalQuantities();

            // Sum up the total quantity of signs
            $totalSigns += $totalQuantities['totalQuantity'];
        }

        return $totalSigns;
    }

    public function calculateTotalTimeBehind(array $warehouseOrders, string $printer): string
    {
        $totalTimeBehind = 0;

        $batchSizes = [
            WarehousePrinterEnum::PRINTER_1 => [
                '24x18' => ['SS' => 20, 'DS' => 20],
                '18x12' => ['SS' => 36, 'DS' => 36],
            ],
            WarehousePrinterEnum::PRINTER_2 => [
                '24x18' => ['SS' => 20, 'DS' => 20],
                '18x12' => ['SS' => 36, 'DS' => 36],
            ],
            WarehousePrinterEnum::PRINTER_3 => [
                '24x18' => ['SS' => 10, 'DS' => 10],
                '18x12' => ['SS' => 20, 'DS' => 20],
            ],
            WarehousePrinterEnum::PRINTER_4 => [
                '24x18' => ['SS' => 10, 'DS' => 10],
                '18x12' => ['SS' => 20, 'DS' => 20],
            ],
            WarehousePrinterEnum::PRINTER_5 => [
                '24x18' => ['SS' => 20, 'DS' => 20],
                '18x12' => ['SS' => 40, 'DS' => 40],
            ],
            WarehousePrinterEnum::PRINTER_6 => [
                '24x18' => ['SS' => 20, 'DS' => 20],
                '18x12' => ['SS' => 40, 'DS' => 40],
            ],
        ];

        $timePerBatch = [
            WarehousePrinterEnum::PRINTER_1 => [
                '24x18' => ['SS' => 10, 'DS' => 20],
                '18x12' => ['SS' => 10, 'DS' => 20],
            ],
            WarehousePrinterEnum::PRINTER_2 => [
                '24x18' => ['SS' => 7, 'DS' => 14],
                '18x12' => ['SS' => 7, 'DS' => 14],
            ],
            WarehousePrinterEnum::PRINTER_3 => [
                '24x18' => ['SS' => 10, 'DS' => 20],
                '18x12' => ['SS' => 10, 'DS' => 20],
            ],
            WarehousePrinterEnum::PRINTER_4 => [
                '24x18' => ['SS' => 3, 'DS' => 6],
                '18x12' => ['SS' => 3, 'DS' => 6],
            ],
            WarehousePrinterEnum::PRINTER_5 => [
                '24x18' => ['SS' => 4, 'DS' => 8],
                '18x12' => ['SS' => 4, 'DS' => 8],
            ],
            WarehousePrinterEnum::PRINTER_6 => [
                '24x18' => ['SS' => 4, 'DS' => 8],
                '18x12' => ['SS' => 4, 'DS' => 8],
            ],
            WarehousePrinterEnum::PRINTER_7 => [
                '24x18' => ['SS' => 4, 'DS' => 8],
                '18x12' => ['SS' => 4, 'DS' => 8],
            ],
            WarehousePrinterEnum::PRINTER_8 => [
                '24x18' => ['SS' => 4, 'DS' => 8],
                '18x12' => ['SS' => 4, 'DS' => 8],
            ],
            WarehousePrinterEnum::PRINTER_9 => [
                '24x18' => ['SS' => 4, 'DS' => 8],
                '18x12' => ['SS' => 4, 'DS' => 8],
            ],
            WarehousePrinterEnum::PRINTER_10 => [
                '24x18' => ['SS' => 4, 'DS' => 8],
                '18x12' => ['SS' => 4, 'DS' => 8],
            ],
        ];

        foreach ($warehouseOrders as $warehouseOrder) {
            $order = $warehouseOrder->getOrder();
            $totalQuantities = $order->getTotalQuantities();

            foreach ($totalQuantities['sizes'] as $size) {
                if (stripos($size, 'WIRE_STAKE') !== false || stripos($size, 'SAMPLE') !== false) {
                    continue;
                }

                $quantity = $totalQuantities['quantitiesBySize'][$size] ?? 0;
                $sides = $totalQuantities['sides'];

                $sizeCategory = $this->getSizeCategory($size);

                if (isset($timePerBatch[$printer][$sizeCategory][$sides]) && isset($batchSizes[$printer][$sizeCategory][$sides])) {
                    $timePerJob = $timePerBatch[$printer][$sizeCategory][$sides];
                    $batchSize = $batchSizes[$printer][$sizeCategory][$sides];

                    $timePerSign = $timePerJob / $batchSize;

                    $totalTimeBehind += $quantity * $timePerSign;
                }
            }
        }

        $hours = floor($totalTimeBehind / 60);
        $minutes = fmod($totalTimeBehind, 60);

        if ($hours >= 24) {
            $days = floor($hours / 24);
            $hours = fmod($hours, 24);
            return sprintf('%dd %dh %dm', $days, $hours, $minutes);
        }

        return sprintf('%dh %dm', $hours, $minutes);
    }

    /**
     * Determines the size category based on the size.
     * Sizes larger than 18x12 use the 24x18 time variables.
     * Sizes smaller than or equal to 18x12 use the 18x12 time variables.
     */
    private function getSizeCategory(string $size): string
    {
        // Parse the size into width and height
        list($width, $height) = explode('x', $size);

        // Calculate the area of the size
        $area = (int)$width * (int)$height;

        // Define the area threshold for 18x12
        $thresholdArea = 18 * 12;

        // Determine the size category
        return ($area > $thresholdArea) ? '24x18' : '18x12';
    }
}
