<?php

namespace App\Controller\Admin\Component\Order;

use App\Entity\Order;
use App\Service\OrderLogger;
use App\Enum\OrderStatusEnum;
use App\Helper\ShippingChartHelper;
use App\Entity\Admin\WarehouseOrder;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Ups\TimeInTransitPayload;
use App\Service\EasyPost\EasyPostShipment;
use App\Service\Ups\UpsTimeInTransitService;
use App\Enum\Admin\WarehouseShippingServiceEnum;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent(
    name: "ReadyForShipmnentComponent",
    template: "admin/components/order/ready_for_shipment.html.twig"
)]
class ReadyForShipmnentComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Order $order = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseService $warehouseService,
        private readonly OrderLogger $logger,
        private readonly EasyPostShipment $easyPostShipment,
        private readonly UpsTimeInTransitService $estimatedDelivery,
        private readonly ShippingChartHelper $shippingChart
    ){}

    #[LiveAction]
    public function markReadyToShip(): void
    {
        if (!in_array($this->order->getStatus(), [OrderStatusEnum::PROOF_APPROVED])) {
            $this->addFlash('danger', 'Order is not in Proof Approved status');
            return;
        }

        $epAddress = $this->order->getMetaDataKey('epShippingAddress');
        if (!$epAddress || (isset($epAddress['id']) && !$epAddress['id'])) {
            $this->addFlash('danger', 'Please validate EP shipping address first.');
            return;
        }

        $this->order->setStatus(OrderStatusEnum::SENT_FOR_PRODUCTION);
        $this->order->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($this->order);
        $this->entityManager->flush();

        if (!$this->order->getWarehouseOrder() instanceof WarehouseOrder) {
            $user = $this->getUser();
            $this->warehouseService->setAdminUser($user);
            $this->warehouseService->getWarehouseOrder($this->order);
        }

        $warehouseOrder = $this->warehouseService->getWarehouseOrder($this->order);
        $oldWarehouseOrder = clone $warehouseOrder;

        $shippingAddress = $this->order->getShippingAddress();

        $epAddress = $this->easyPostShipment->getAddress($this->easyPostShipment->getFromAddressId());

        $timeInTransitPayload = new TimeInTransitPayload(
            originCountryCode: 'US',
            originPostalCode: '77479',
            originStateProvince: 'TX',
            originCityName: 'Sugar Land',
            destinationCountryCode: $shippingAddress['country'],
            destinationStateProvince: $shippingAddress['state'],
            destinationPostalCode: $shippingAddress['zipcode'],
            destinationCityName: $shippingAddress['city']
        );

        $daysInTransit = $this->estimatedDelivery->retrieveDaysInTransit($timeInTransitPayload);

        $this->order->setMetaDataKey('epDaysInTransit', $daysInTransit);

        if ($warehouseOrder instanceof WarehouseOrder && $daysInTransit && $this->order->getDeliveryDate()) {
            $warehouseOrder->setShipBy($this->shippingChart->calculateShipByDate($this->order->getDeliveryDate(), $daysInTransit));
            $warehouseOrder->setShippingService(WarehouseShippingServiceEnum::UPS_GROUND);
            $this->logger->setOrder($this->order);
            $this->logger->log('Shipping date has been updated to ' . $warehouseOrder->getShipBy()->format('Y-m-d') . ' and shipping service to UPS Ground', $this->getUser());
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();
        }

        if ($warehouseOrder instanceof WarehouseOrder){
            if ($warehouseOrder->getPrinterName() && $warehouseOrder->getShipBy()) {
                $this->warehouseService->activateShipByList($warehouseOrder);
                $this->warehouseService->logChange($oldWarehouseOrder, $warehouseOrder);
            }
        }

        $this->logger->setOrder($this->order);
        $this->logger->log('Order has been moved to Ready for Production', $this->getUser());

        $this->addFlash('success', 'Order has been moved to Ready for Production');
        $this->entityManager->refresh($this->order);
    }

    #[LiveAction]
    public function regenerateShipByDate(): void
    {
        $warehouseOrder = $this->warehouseService->getWarehouseOrder($this->order);

        $shippingAddress = $this->order->getShippingAddress();

        $timeInTransitPayload = new TimeInTransitPayload(
            originCountryCode: 'US',
            originPostalCode: '77479',
            originStateProvince: 'TX',
            originCityName: 'Sugar Land',
            destinationCountryCode: $shippingAddress['country'],
            destinationStateProvince: $shippingAddress['state'],
            destinationPostalCode: $shippingAddress['zipcode'],
            destinationCityName: $shippingAddress['city']
        );

        $daysInTransit = $this->estimatedDelivery->retrieveDaysInTransit($timeInTransitPayload);

        $this->order->setMetaDataKey('epDaysInTransit', $daysInTransit);

        if ($warehouseOrder instanceof WarehouseOrder && $daysInTransit && $this->order->getDeliveryDate()) {
            $warehouseOrder->setShipBy($this->shippingChart->calculateShipByDate($this->order->getDeliveryDate(), $daysInTransit));
            $warehouseOrder->setShippingService(WarehouseShippingServiceEnum::UPS_GROUND);
            $this->logger->log('Shipping date has been updated to ' . $warehouseOrder->getShipBy()->format('Y-m-d') . ' and shipping service to UPS Ground', $this->getUser());
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();
        }

        if ($warehouseOrder instanceof WarehouseOrder) {
            if ($warehouseOrder->getPrinterName() && $warehouseOrder->getShipBy()) {
                $this->warehouseService->activateShipByList($warehouseOrder);
            }
        }

        $this->addFlash('success', 'Order has been moved to Ready for Production');
        $this->entityManager->refresh($this->order);
    }

}
