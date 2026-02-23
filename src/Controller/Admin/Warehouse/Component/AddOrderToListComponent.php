<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseShipByList;
use App\Entity\Order;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Form\Admin\Warehouse\AddOrderToListType;
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
    name: "AdminWarehouseAddOrderToList",
    template: "admin/warehouse/components/add-order-to-list.html.twig"
)]
class AddOrderToListComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public string $printerName;

    #[LiveProp]
    public WarehouseShipByList $shipByList;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(AddOrderToListType::class, null, [
            'printerName' => $this->printerName,
            'shipByList' => $this->shipByList
        ]);
    }

    #[LiveAction]
    public function save(): Response|null
    {
        $this->denyAccessUnlessGranted('warehouse_add_order_to_ship_by_list');
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->updateWarehouseOrder($data['order']);
            $this->addFlash('success', 'Order added to Warehouse Order Queue in Ship By list ' . $this->shipByList->getShipBy()->format('D m/d/y') . ' for ' . $this->printerName . ' printer.');
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $this->printerName]);
        }
        return null;
    }

    private function updateWarehouseOrder(Order $order)
    {
        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['order' => $order]);
        if (!$warehouseOrder) {
            $warehouseOrder = new WarehouseOrder();
            $warehouseOrder->setOrder($order);
            $warehouseOrder->setShipBy($this->shipByList->getShipBy());
            $warehouseOrder->setPrinterName($this->printerName);
            $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::READY);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();
            $this->warehouseService->setAdminUser($this->getUser());
            $this->warehouseService->addWarehouseOrderLog($warehouseOrder, 'Order added to Warehouse Order Queue from Ship By List');
        } else {
            $message = '';
            if ($warehouseOrder->getPrinterName() && $warehouseOrder->getPrinterName() !== $this->printerName) {
                $message .= 'Printer Name updated from <b>' . $warehouseOrder->getPrinterName() . '</b> to <b>' . $this->printerName . "</b>\n";
            }
            if ($warehouseOrder->getShipBy() && $warehouseOrder->getShipBy()->getTimestamp() !== $this->shipByList->getShipBy()->getTimestamp()) {
                $message .= 'Ship By updated from <b>' . $warehouseOrder->getShipBy()->format('D m/d/y') . '</b> to <b>' . $this->shipByList->getShipBy()->format('D m/d/y') . "</b>\n";
            }
            $warehouseOrder->setShipBy($this->shipByList->getShipBy());
            $warehouseOrder->setPrinterName($this->printerName);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();

            if (!empty($message)) {
                $this->warehouseService->setAdminUser($this->getUser());
                $this->warehouseService->addWarehouseOrderLog($warehouseOrder, "AddOrderToList\n" . $message);
            }
        }

        $order->setPrinterName($this->printerName);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $warehouseOrder;
    }

}
