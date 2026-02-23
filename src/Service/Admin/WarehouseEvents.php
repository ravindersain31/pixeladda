<?php

namespace App\Service\Admin;

use App\Entity\Admin\WarehouseOrder;
use App\Service\MercureEventPublisher;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Admin\WarehouseShipByList;
use App\Enum\Admin\WarehouseMercureEventEnum;
use App\Repository\Admin\WarehouseOrderRepository;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\Admin\WarehouseOrderGroupRepository;
use App\Repository\Admin\WarehouseShipByListRepository;
use App\Twig\WarehouseProvider;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class WarehouseEvents
{

    private ?string $triggeredBySession = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseOrderRepository $warehouseOrderRepository,
        private readonly WarehouseOrderGroupRepository $warehouseOrderGroupRepository,
        private readonly SerializerInterface $serializer,
        private readonly MercureEventPublisher $mercurePublisher,
        private readonly WarehouseShipByListRepository $warehouseShipByListRepository,
        private readonly WarehouseService $warehouseService,
        private readonly WarehouseProvider $warehouseProvider
    ) {
    }

    public function setTriggeredBySession(?string $sessionId): void
    {
        $this->triggeredBySession = $sessionId;
    }

    public function sessionHeartbeatEvent(): void
    {
        $this->mercurePublisher->publishHeartbeat(
            topic: WarehouseMercureEventEnum::WAREHOUSE_HEARTBEAT,
        );
    }

    public function printerWithCountEvent(?string $search = null): void
    {
        $printers = $this->warehouseProvider->getPrintersWithCount(search: $search);
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_PRINTERS_COUNT,
            [
                'printers' => $printers,
            ]
        );
    }

    public function createShipByListEvent(string $printer): void
    {
        $lists = $this->entityManager->getRepository(WarehouseShipByList::class)->findActiveListByPrinter($printer);

        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_CREATED_SHIP_BY_LIST,
            [
                'printer' => $printer,
                'lists' => json_decode($this->serializer->serialize($lists, 'json', [AbstractNormalizer::GROUPS => ['apiData']]))
            ]
        );
    }

    public function notifyConnectionStatus(): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_PRINTER_CONNECTION_STATUS,
            [
                'isConnectionActive' => true,
            ]
        );
    }


    public function updateShipByListEvent(array $shipByList, string $printer): void
    {
        $shipByOrders = $this->warehouseService->getWarehouseListByShipBy(shipBy: $shipByList, printer: $printer);
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATED_SHIP_BY_LIST,
            [
                'printer' => $printer,
                'shipByOrders' => $shipByOrders,
            ]
        );
    }

    public function changedShipByListEvent(WarehouseOrder $warehouseOrder, bool $isListUpdated = false): void
    {
        if ($isListUpdated) {
            $this->updateShipByListEvent([$warehouseOrder->getShipBy()->format('Y-m-d')], $warehouseOrder->getPrinterName());
        }

        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_CHANGED_SHIP_BY,
            [
                'warehouseOrderId' => $warehouseOrder->getId(),
            ]
        );
    }

    public function updateProofPrintedEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATED_PRINTED,
            [
                'id' => $warehouseOrder->getId(),
                'isProofPrinted' => $warehouseOrder->isIsProofPrinted(),
            ]
        );
    }

    public function updateNotesEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATED_NOTES,
            [
                'id' => $warehouseOrder->getId(),
                'comments' => $warehouseOrder->getComments() ?? '',
            ]
        );
    }

    public function updateCommentLogsEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATE_LOGS,
            [
                'id' => $warehouseOrder->getId(),
                'logs' => json_decode($this->serializer->serialize($warehouseOrder->getWarehouseOrderLogs(), 'json', [AbstractNormalizer::GROUPS => ['apiData']]))
            ]
        );
    }

    public function removeCommentLogsEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATE_LOGS,
            [
                'id' => $warehouseOrder->getId(),
                'logs' => json_decode($this->serializer->serialize($warehouseOrder->getWarehouseOrderLogs(), 'json', [AbstractNormalizer::GROUPS => ['apiData']]))
            ]
        );
    }

    public function updatePrintStatusEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATED_PRINT_STATUS,
            [
                'id' => $warehouseOrder->getId(),
                'printStatus' => $warehouseOrder->getPrintStatus(),
            ]
        );
    }

    public function removeShipByListEvent(WarehouseShipByList $list): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_REMOVE_SHIP_BY_LIST,
            [
                'id' => $list->getId(),
                'isDeleted' => true
            ]
        );
    }

    public function removeWarehouseOrderEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_REMOVED,
            [
                'printer' => $warehouseOrder->getPrinterName(),
                'warehouseOrderId' => $warehouseOrder->getId(),
            ]
        );
    }

    public function updateSortIndexEvent(string $printer, array $shipBy): void
    {
        $shipByOrders = $this->warehouseService->getWarehouseListByShipBy(shipBy: $shipBy, printer: $printer);

        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATED_SORT_INDEX,
            [
                'printer' => $printer,
                'shipByOrders' => $shipByOrders,
            ],
            options: ['triggeredBySession' => $this->triggeredBySession]
        );
    }

    public function updateWarehouseOrder(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_UPDATED,
            [
                'id' => $warehouseOrder->getId(),
                'warehouseOrder' => json_decode($this->serializer->serialize($warehouseOrder, 'json', [AbstractNormalizer::GROUPS => ['apiData']]))
            ]
        );
    }

    public function markDoneEvent(WarehouseOrder $warehouseOrder, string $type): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_MARK_DONE,
            [
                'printer' => $warehouseOrder->getPrinterName(),
                'warehouseOrderId' => $warehouseOrder->getId(),
                'type' => $type,
            ]
        );
    }

    public function markFreightShippingDoneEvent(WarehouseOrder $warehouseOrder, string $type): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_FREIGHT_SHIPPING_DONE,
            [
                'printer' => $warehouseOrder->getPrinterName(),
                'warehouseOrderId' => $warehouseOrder->getId(),
                'type' => $type,
            ]
        );
    }

    public function markPickupDoneEvent(WarehouseOrder $warehouseOrder, string $type): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_PICKUP_DONE,
            [
                'printer' => $warehouseOrder->getPrinterName(),
                'warehouseOrderId' => $warehouseOrder->getId(),
                'type' => $type,
            ]
        );
    }

    public function markDoneReadyForShipmentEvent(WarehouseOrder $warehouseOrder, string $type): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_MARK_DONE_READY_FOR_SHIPMENT,
            [
                'printer' => $warehouseOrder->getPrinterName(),
                'warehouseOrderId' => $warehouseOrder->getId(),
                'type' => $type,
            ]
        );
    }

    public function createOrUpdateShipByListEvent(array $shipByList, string $printer): void
    {
        $this->createShipByListEvent($printer);
        $this->updateShipByListEvent($shipByList, $printer);
        $this->updateShipByListEvent($shipByList, $printer);
        $this->printerWithCountEvent();
    }

}