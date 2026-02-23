<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Form\Admin\Warehouse\ReQueueOrderType;
use App\Service\Admin\WarehouseEvents;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseRequeueOrderComponent",
    template: "admin/warehouse/components/requeue-order.html.twig"
)]
class RequeueOrderComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public WarehouseOrder $warehouseOrder;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseService $warehouseService,
        private readonly WarehouseEvents $warehouseEvents
    ){
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ReQueueOrderType::class, null, [
            'warehouseOrder' => $this->warehouseOrder,
        ]);
    }

    #[LiveAction]
    public function save(): Response|null
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->warehouseService->setAdminUser($this->getUser());

            $this->warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::READY);
            $this->warehouseOrder->setShipBy($data['shipBy']);
            $this->warehouseOrder->setPrinterName($data['printerName']);
            $this->entityManager->persist($this->warehouseOrder);

            $userOrder = $this->warehouseOrder->getOrder();
            $userOrder->setPrinterName($data['printerName']);
            $this->entityManager->persist($userOrder);
            $this->entityManager->flush();

            $shipByDateFormatted = $this->warehouseOrder->getShipBy()->format('D m/d');
            $message = "Order #" . $userOrder->getOrderId() . " has been successfully re-added to the order queue for Ship By " . $shipByDateFormatted . " and Printer " . $this->warehouseOrder->getPrinterName();
            $this->warehouseService->addWarehouseOrderLog($this->warehouseOrder, $message);

            if (!empty($data['comments'])) {
                $this->warehouseService->addWarehouseOrderLog($this->warehouseOrder, $data['comments']);
            }

            $this->warehouseService->activateShipByList($this->warehouseOrder);
            $warehouseOrder = $this->warehouseOrder;
            $this->warehouseEvents->createOrUpdateShipByListEvent(shipByList: [$warehouseOrder->getShipBy()->format('Y-m-d')], printer: $warehouseOrder->getPrinterName());

            $this->addFlash('success', $message);
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $this->warehouseOrder->getPrinterName()]);
        }
        return null;
    }

}
