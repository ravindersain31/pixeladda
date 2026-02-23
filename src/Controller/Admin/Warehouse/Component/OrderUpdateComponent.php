<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Enum\Admin\WarehouseShippingServiceEnum;
use App\Enum\OrderTagsEnum;
use App\Form\Admin\Warehouse\OrderUpdateType;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use App\Service\Admin\WarehouseEvents;

#[AsLiveComponent(
    name: "AdminWarehouseOrderUpdate",
    template: "admin/warehouse/components/order-update-form.html.twig"
)]
class OrderUpdateComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?WarehouseOrder $warehouseOrder;

    #[LiveProp]
    public ?Order $order;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseService $warehouseService,
        private readonly WarehouseEvents $warehouseEvents

    ){
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrderUpdateType::class, null, [
            'warehouseOrder' => $this->warehouseOrder,
            'order' => $this->order,
        ]);
    }

    #[LiveAction]
    public function save(): Response|null
    {
        $initialWarehouseOrder = clone $this->warehouseOrder;
        $this->denyAccessUnlessGranted('warehouse_update_order_details');

        $oldWarehouseOrder = $this->warehouseOrder instanceof WarehouseOrder ? clone $this->warehouseOrder : null;
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->warehouseService->setAdminUser($this->getUser());
            $warehouseOrder = $this->warehouseService->getWarehouseOrder($this->order, $this->warehouseOrder);

            $userOrder = $this->order;
            if ($userOrder === null) {
                $userOrder = $warehouseOrder->getOrder();
            }
            $userOrder->setPrinterName($data['printerName']);
            $this->entityManager->persist($userOrder);
            $mustShip = $data['mustShip'];
            $targetShipBy = $data['shipBy'];
            if ($mustShip) {
                $targetShipBy = $mustShip;
            }
            
            $shipBy = $targetShipBy;
            $warehouseOrder->setShipBy($shipBy);
            $warehouseOrder->setPrinterName($data['printerName']);
            $warehouseOrder->setShippingService($data['shippingService']);
            $warehouseOrder->setDriveLink($data['driveLink']);
            if ($warehouseOrder->getNotes()) {
                $warehouseOrder->setComments($warehouseOrder->getNotes());
            }
            if($mustShip) {
                $warehouseOrder->setSortIndex(-1);
            }

            if (
                $data['printerName'] !== $initialWarehouseOrder->getPrinterName() ||
                (
                    $initialWarehouseOrder->getShipBy() instanceof \DateTimeImmutable && $shipBy !== null &&
                    $initialWarehouseOrder->getShipBy()->format('Y-m-d') !== $shipBy->format('Y-m-d')
                )
            ) {
                $warehouseOrder->setWarehouseOrderGroup(null);
            }

            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();

            $this->warehouseService->activateShipByList($warehouseOrder);
            $this->warehouseEvents->createOrUpdateShipByListEvent(shipByList: [$warehouseOrder->getShipBy()->format('Y-m-d')], printer: $warehouseOrder->getPrinterName());

            $this->updateOrderTags($userOrder, $data['orderTag']);
            $this->updateMustShip($userOrder, $data['mustShip']);

            if ($oldWarehouseOrder instanceof WarehouseOrder) {
                $this->logChange($oldWarehouseOrder, $warehouseOrder);
            }

            if ($warehouseOrder->getPrinterName() && $warehouseOrder->getShipBy()) {
                $shipByDateFormatted = $warehouseOrder->getShipBy()->format('D m/d');
                $this->addFlash('success', "Order #" . $this->order->getOrderId() . " has been successfully added to the order queue for Ship By " . $shipByDateFormatted . " and Printer " . $warehouseOrder->getPrinterName());
                return $this->redirectToRoute('admin_warehouse_queue_by_printer', ["printer" => $warehouseOrder->getPrinterName()]);
            }

            $this->addFlash('success', "Order #" . $this->order->getOrderId() . " has been successfully updated.");
            return $this->redirectToRoute('admin_warehouse_queue_shipping_easy');
        }
        return null;
    }

    private function updateOrderTags(Order $order, array $orderTags): void
    {
        $customTags = [];
        foreach (OrderTagsEnum::ALL_TAGS as $key => $name) {
            $customTags[$key] = [
                'name' => $name,
                'active' => in_array($key, $orderTags, true),
            ];
        }

        $order->setMetaDataKey('tags', $customTags);
        $order->setIsFreightRequired($customTags['FREIGHT']['active']);

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

    private function updateMustShip(Order $order, ?\DateTimeInterface $mustShip): void
    {
        if ($mustShip !== null) {
            $data = [
                'name' => 'Must Ship '. $mustShip->format('M j, Y'),
                'date' => $mustShip->format('Y-m-d'),
            ];
            $order->setMetaDataKey('mustShip', $data);
        } else {
            $order->setMetaDataKey('mustShip', null);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    private function logChange(WarehouseOrder $oldWarehouseOrder, WarehouseOrder $newWarehouseOrder): void
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
            $this->warehouseService->setAdminUser($this->getUser());
            $this->warehouseService->addWarehouseOrderLog($newWarehouseOrder, $content);
        }
    }

}
