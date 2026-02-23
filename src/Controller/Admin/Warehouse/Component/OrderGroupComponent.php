<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderGroup;
use App\Entity\Admin\WarehouseOrderLog;
use App\Form\Admin\Warehouse\orderGroupType;
use App\Form\Admin\Warehouse\OrderLogType;
use App\Repository\Admin\WarehouseOrderRepository;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "OrderGroupComponent",
    template: "admin/warehouse/components/order-group-form.html.twig"
)]
class OrderGroupComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public ?WarehouseOrder $warehouseOrder;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseOrderRepository $warehouseOrderRepository,
        private readonly WarehouseService $warehouseService
    ){}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(orderGroupType::class, null, [
            'warehouseOrder' => $this->warehouseOrder
        ]);
    }

    #[LiveAction]
    public function save(): Response
    {
        $warehouseOrderGroup = $this->warehouseOrder->getWarehouseOrderGroup();

        if ($warehouseOrderGroup) {
            $initialWarehouseOrderGroup = clone $warehouseOrderGroup->getOrderGroup();
        } else {
            $initialWarehouseOrderGroup = new \Doctrine\Common\Collections\ArrayCollection();
        }

        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {

            $groupData = $form->get('group')->getData();
            $response = [
                'success' => false,
                'message' => ''
            ];

            if($response['success']) {
                $this->addFlash('success', $response['message']);
                return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $this->warehouseOrder->getPrinterName()]);
            }else {
                $this->addFlash('danger', $response['message']);
                return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $this->warehouseOrder->getPrinterName()]);
            }
        }

        $this->addFlash('danger', 'Failed to add order group');
        return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $this->warehouseOrder->getPrinterName()]);
    }

}
