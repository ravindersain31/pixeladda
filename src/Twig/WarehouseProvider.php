<?php

namespace App\Twig;

use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\WarehousePrinterEnum;
use App\Repository\Admin\WarehouseOrderRepository;
use App\Repository\OrderRepository;

class WarehouseProvider
{
    public function __construct(
        private readonly WarehouseOrderRepository $warehouseOrderRepository,
        private readonly OrderRepository $orderRepository,
    ){
    }

    public function getPrintersWithCount(?string $search = null): array
    {
        $printers = WarehousePrinterEnum::PRINTERS;
        $printerStatus = [WarehouseOrderStatusEnum::DONE];
        $ordersCounts = $this->warehouseOrderRepository->countBy(
            printers: array_keys($printers),
            hasPrintStatus: false,
            printStatus: $printerStatus,
            search: $search,
        )->getOneOrNullResult();

        foreach ($printers as $key => $print) {
            $printers[$key]['orderCount'] = $ordersCounts[$key];
        }
        return $printers;
    }

    public function getUnassignedCount(?string $search = null): int
    {
        $printerStatus = [OrderStatusEnum::READY_FOR_SHIPMENT];
        $ordersCounts = $this->warehouseOrderRepository->countBy(
            printers: [WarehousePrinterEnum::UNASSIGNED],
            orderStatus: [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT],
            onlyUnassigned: true,
            search: $search,
        )->getOneOrNullResult();
        return $ordersCounts['UNASSIGNED'] ?? 0;
    }

    public function getCreateShipmentCount(?string $search = null): int
    {
        $count = $this->orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            onlyCount: true,
            hasShipping: 'no',
        )->getOneOrNullResult();

        return $count['totalOrders'] ?? 0;
    }

    public function getReadyToShipCount(?string $search = null): int
    {
        $count = $this->orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            onlyCount: true,
            hasShipping: 'yes',
        )->getOneOrNullResult();

        return $count['totalOrders'] ?? 0;
    }

    public function getReadyForShipmentCount(?string $search = null): int
    {
        $count = $this->orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            onlyCount: true,
        )->getOneOrNullResult();

        return $count['totalOrders'] ?? 0;
    }

    public function getOrdersCountWithPreTransitStatus(): int
    {
        $query = $this->warehouseOrderRepository->getOrdersWithPreTransitStatus();

        return count($query ?? []);
    }


}