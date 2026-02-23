<?php

namespace App\Service\Admin;

use App\Entity\Order;
use App\Entity\AdminUser;
use App\Entity\Admin\WarehouseOrder;
use App\Service\MercureEventPublisher;
use App\Entity\Admin\WarehouseOrderLog;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Admin\WarehouseShipByList;
use App\Enum\Admin\WarehouseMercureEventEnum;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\Admin\WarehouseShippingServiceEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTagsEnum;
use App\Repository\Admin\WarehouseOrderRepository;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\Admin\WarehouseOrderGroupRepository;
use App\Repository\Admin\WarehouseShipByListRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WarehouseService extends AbstractController
{
    private AdminUser $user;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseOrderRepository $warehouseOrderRepository,
        private readonly WarehouseOrderGroupRepository $warehouseOrderGroupRepository,
        private readonly SerializerInterface $serializer,
        private readonly MercureEventPublisher $mercurePublisher,
        private readonly WarehouseShipByListRepository $warehouseShipByListRepository
    ) {}

    public function setAdminUser(AdminUser|UserInterface|null $user): void
    {
        $this->user = $user;
    }

    public function getWarehouseOrder(Order $order, ?WarehouseOrder $warehouseOrder = null): WarehouseOrder
    {
        if (!$warehouseOrder) {
            $warehouseOrder = $order->getWarehouseOrder();
        }
        if ($warehouseOrder instanceof WarehouseOrder) {
            return $warehouseOrder;
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['order' => $order]);
        if (!$warehouseOrder) {
            $warehouseOrder = new WarehouseOrder();
            $warehouseOrder->setOrder($order);
            $warehouseOrder->setPrinterName($order->getPrinterName());
            $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::READY);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();
            $this->addWarehouseOrderLog($warehouseOrder, 'Order added to Warehouse Order Queue');
        }
        return $warehouseOrder;
    }

    public function addWarehouseOrderLog(WarehouseOrder $order, string $content): void
    {
        $log = new WarehouseOrderLog();
        $log->setOrder($order);
        $log->setLoggedBy($this->getUser());
        $log->setContent($content);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function activateShipByList(WarehouseOrder $order)
    {
        if ($order->getShipBy() instanceof \DateTimeImmutable || $order->getShipBy() instanceof \DateTime && $order->getPrinterName()) {
            $shipByList = $this->entityManager->getRepository(WarehouseShipByList::class)->findOneBy([
                'printerName' => $order->getPrinterName(),
                'shipBy' => $order->getShipBy(),
            ]);
            if (!$shipByList instanceof WarehouseShipByList) {
                $shipByList = new WarehouseShipByList();
                $shipByList->setPrinterName($order->getPrinterName());
                $shipByList->setShipBy($order->getShipBy());
            }
            $shipByList->setDeletedAt(null);
            $shipByList->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($shipByList);
            $this->entityManager->flush();
            return $shipByList;
        }
        return null;
    }

    public function isWarehouseOrderShipByListActive(WarehouseOrder $order, ?string $printerName = null): bool
    {
        $printerName = $printerName ?? $order->getPrinterName();
        if ($order->getShipBy() instanceof \DateTimeImmutable && $printerName) {
            return $this->isShipByListActiveByDateAndPrinter($order->getShipBy(), $printerName);
        }

        return false;
    }

    public function isShipByListActiveByDateAndPrinter(\DateTimeImmutable $shipBy, string $printer): bool
    {
        return $this->warehouseShipByListRepository->isShipByListActive($shipBy, $printer);
    }

    public function updateOrderTags(Order $order, array $orderTags): void
    {
        $customTags = [];

        foreach (OrderTagsEnum::ALL_TAGS as $key => $name) {
            $customTags[$key] = [
                'name' => $name,
                'active' => in_array($key, $orderTags, true),
            ];
        }

        $order->setMetaDataKey('tags', $customTags);
        $order->setIsFreightRequired($customTags[OrderTagsEnum::FREIGHT]['active']);

        $requestPickup = in_array('REQUEST_PICKUP', $orderTags);
        $blindShipping = in_array('BLIND_SHIPPING', $orderTags);
        $isSaturdayDelivery = in_array('SATURDAY_DELIVERY', $orderTags);
        $freight = in_array('FREIGHT', $orderTags);

        $isSuperRush = in_array('SUPER_RUSH', $orderTags);

        $order->setIsSuperRush($isSuperRush);

        $order->setMetaDataKey('isFreeFreight', $freight);
        $order->setMetaDataKey('isBlindShipping', $blindShipping);
        $order->setMetaDataKey('isSaturdayDelivery', $isSaturdayDelivery);
        $order->setMetaDataKey('deliveryMethod', [
            "key" => $requestPickup ? "REQUEST_PICKUP" : "DELIVERY",
            "type" => "percentage",
            "label" => $requestPickup ? "Request Pickup" : "Delivery",
            "discount" => 0
        ]);

        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function logChange(WarehouseOrder $oldWarehouseOrder, WarehouseOrder $newWarehouseOrder): void
    {
        $content = '';
        $dateFormat = 'D M d, Y';
        if ($oldWarehouseOrder->getPrinterName() !== $newWarehouseOrder->getPrinterName()) {
            $newWarehouseOrder->getOrder()->setPrinterName($newWarehouseOrder->getPrinterName());
            $this->entityManager->persist($newWarehouseOrder->getOrder());
            $this->entityManager->flush();
            $newPrinterName = $newWarehouseOrder->getPrinterName() ?? "Unassigned";
            if ($oldWarehouseOrder->getPrinterName()) {
                $content .= 'Printer updated from <b>' . $oldWarehouseOrder->getPrinterName() . '</b> to <b>' . $newPrinterName . "</b>\n";
            } else {
                $content .= 'Printer added <b>' . $newPrinterName . "</b>\n";
            }
        }
        if ($oldWarehouseOrder->getShipBy() !== $newWarehouseOrder->getShipBy()) {
            if ($oldWarehouseOrder->getShipBy()) {
                $newShipByDate = $newWarehouseOrder->getShipBy()?->format($dateFormat) ?? "Unassigned";
                $content .= 'Ship By updated from <b>' . $oldWarehouseOrder->getShipBy()->format($dateFormat) . '</b> to <b>' . $newShipByDate . "</b>\n";
            } else {
                $content .= 'Ship By added <b>' . $newWarehouseOrder->getShipBy()->format($dateFormat) . "</b>\n";
            }
        }
        if ($oldWarehouseOrder->getDriveLink() !== $newWarehouseOrder->getDriveLink()) {
            if ($oldWarehouseOrder->getDriveLink()) {
                $content .= 'Drive Link update from <b><a href="' . $oldWarehouseOrder->getDriveLink() . '" target="_blank">Old Link</a></b> to <b><a href="' . $newWarehouseOrder->getDriveLink() . '" target="_blank">New Link</a></b>' . "\n";
            } else {
                $content .= 'Drive Link added <b><a href="' . $newWarehouseOrder->getDriveLink() . '" target="_blank">Link</a></b>' . "\n";
            }
        }

        if ($oldWarehouseOrder->getShippingService() !== $newWarehouseOrder->getShippingService()) {
            if ($oldWarehouseOrder->getShippingService()) {
                $content .= 'Shipping Service updated from <b>' . WarehouseShippingServiceEnum::getLabel($oldWarehouseOrder->getShippingService()) . '</b> to <b>' . WarehouseShippingServiceEnum::getLabel($newWarehouseOrder->getShippingService()) . "</b>\n";
            } else {
                $content .= 'Shipping Service added <b>' . WarehouseShippingServiceEnum::getLabel($newWarehouseOrder->getShippingService()) . "</b>\n";
            }
        }

        if (!empty($content)) {
            $this->setAdminUser($this->getUser());
            $this->addWarehouseOrderLog($newWarehouseOrder, $content);
        }
    }

    public function log(WarehouseOrder $warehouseOrder, string $content): void
    {
        $log = new WarehouseOrderLog();
        $log->setOrder($warehouseOrder);
        $log->setLoggedBy($this->getUser());
        $log->setContent($content);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function getGroupOrdersShipBy(array $orders): array
    {
        $groupedOrders = [];

        foreach ($orders as $order) {
            $dateKey = $order->getShipBy()->format('Y-m-d');
            if (!isset($groupedOrders[$dateKey])) {
                $groupedOrders[$dateKey] = [];
            }
            $groupedOrders[$dateKey][] = $order;
        }

        return array_reverse($groupedOrders);
    }

    /**
     * @param string $printer
     * @param array $shipBy
     * @return array
     */
    /*
     * Returns an array of WarehouseOrder objects grouped by shipBy date.
     * The keys of the array are the shipBy dates in the format '25-12-2025'.
     * The values are arrays of WarehouseOrder objects.
     */
    public function getWarehouseListByShipBy(string $printer = 'P1', array $shipBy = []): mixed
    {
        $printerOrders = $this->warehouseOrderRepository->findQueue(printerName: $printer, shipBy: $shipBy)->getResult();

        $ordersShipBy = $this->getGroupOrdersShipBy($printerOrders);

        $finalResult = [];
        foreach ($shipBy as $shipByDate) {
            $finalResult[$shipByDate] = [];
        }

        foreach ($ordersShipBy as $shipByDate => $orders) {
            $finalResult[$shipByDate] = $orders;
        }

        return json_decode($this->serializer->serialize($finalResult, 'json', [AbstractNormalizer::GROUPS => ['apiData']]));
    }



    public function AddOrUpdateWarehouseOrder(Order $order, string $printerName, \DateTimeImmutable $shipBy): WarehouseOrder
    {

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['order' => $order]);
        if (!$warehouseOrder) {
            $warehouseOrder = new WarehouseOrder();
            $warehouseOrder->setOrder($order);
            $warehouseOrder->setShipBy($shipBy);
            $warehouseOrder->setPrinterName($printerName);
            $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::READY);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();
            $this->setAdminUser($this->getUser());
            $this->addWarehouseOrderLog($warehouseOrder, 'Order added to Warehouse Order Queue from Ship By List');
        } else {
            $message = '';
            if ($warehouseOrder->getPrinterName() && $warehouseOrder->getPrinterName() !== $printerName) {
                $message .= 'Printer Name updated from <b>' . $warehouseOrder->getPrinterName() . '</b> to <b>' . $printerName . "</b>\n";
            }
            if ($warehouseOrder->getShipBy() && $warehouseOrder->getShipBy()->getTimestamp() !== $shipBy->getTimestamp()) {
                $message .= 'Ship By updated from <b>' . $warehouseOrder->getShipBy()->format('D m/d/y') . '</b> to <b>' . $shipBy->format('D m/d/y') . "</b>\n";
            }
            // $this->removeWarehouseOrderEvent($warehouseOrder);
            $warehouseOrder->setShipBy($shipBy);
            $warehouseOrder->setPrinterName($printerName);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();

            if (!empty($message)) {
                $this->setAdminUser($this->getUser());
                $this->addWarehouseOrderLog($warehouseOrder, "AddOrderToList\n" . $message);
            }
        }

        $order->setPrinterName($printerName);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $warehouseOrder;
    }

    public function checkIfSubOrdersAreReady($warehouseOrder): bool
    {
        $subOrders = $warehouseOrder->getOrder()->getParent()->getSubOrders();

        foreach ($subOrders as $subOrder) {
            if ($subOrder->getWarehouseOrder()->getOrder()->getStatus() !== OrderStatusEnum::COMPLETED) {
                return false;
            }
        }
        return true;
    }

    public function removeWarehouseOrderEvent(WarehouseOrder $warehouseOrder): void
    {
        $this->mercurePublisher->publishEvent(
            WarehouseMercureEventEnum::WAREHOUSE_ORDER_REMOVED,
            [
                'warehouseOrderId' => $warehouseOrder->getId(),
                'printer' => $warehouseOrder->getPrinterName(),
                'shipBy' => $warehouseOrder->getShipBy()->format('Y-m-d'),
            ]
        );
    }
}
