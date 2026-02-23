<?php

namespace App\EventListener;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Event\OrderAdminPrintedAssignedEvent;
use App\Event\OrderAdminStatusChangeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class AdminOrderEventListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[AsEventListener(event: OrderAdminStatusChangeEvent::NAME)]
    public function onOrderAdminStatusChange(OrderAdminStatusChangeEvent $event): void
    {
        $order = $event->getOrder();

        $warehouseOrder = $this->getWarehouse($order);
        $printerName = !empty($order->getPrinterName()) ? $order->getPrinterName() : null;
        $warehouseOrder->setPrinterName($printerName);
        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();
    }

    #[AsEventListener(event: OrderAdminPrintedAssignedEvent::NAME)]
    public function onOrderAdminPrinterAssigned(OrderAdminPrintedAssignedEvent $event): void
    {
        $order = $event->getOrder();

        $warehouseOrder = $this->getWarehouse($order);
        $printerName = !empty($order->getPrinterName()) ? $order->getPrinterName() : null;
        $warehouseOrder->setPrinterName($printerName);
        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();
    }

    private function getWarehouse(Order $order): WarehouseOrder
    {
        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['order' => $order]);
        if (!$warehouseOrder) {
            $warehouseOrder = new WarehouseOrder();
            $warehouseOrder->setOrder($order);
            $printerName = !empty($order->getPrinterName()) ? $order->getPrinterName() : null;
            $warehouseOrder->setPrinterName($printerName);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();
        }
        return $warehouseOrder;
    }
}