<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Enum\Admin\WarehouseShippingServiceEnum;
use App\Form\Admin\Warehouse\UpdateShippingServiceType;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseUpdateShippingServiceComponent",
    template: "admin/warehouse/components/update-shipping-service.html.twig"
)]
class UpdateShippingServiceComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?WarehouseOrder $warehouseOrder = null;

    #[LiveProp]
    public Order $order;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UpdateShippingServiceType::class, null, [
            'warehouseOrder' => $this->warehouseOrder,
        ]);
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->warehouseService->setAdminUser($this->getUser());
            $warehouseOrder = $this->warehouseService->getWarehouseOrder($this->order, $this->warehouseOrder);

            $warehouseOrder->setShippingService($data['shippingService']);
            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();

            $content = 'Shipping Service updated to <b>' . WarehouseShippingServiceEnum::getLabel($warehouseOrder->getShippingService()) . '</b>';
            $this->warehouseService->addWarehouseOrderLog($warehouseOrder, $content);

            return $this->redirect($this->generateUrl('admin_warehouse_queue_ready_shipping_easy'));
        }
        return null;
    }


}
