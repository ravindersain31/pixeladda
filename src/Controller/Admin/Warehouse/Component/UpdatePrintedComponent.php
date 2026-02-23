<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseUpdatePrintedComponent",
    template: "admin/warehouse/components/update-printed-count.html.twig"
)]
class UpdatePrintedComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public WarehouseOrder $warehouseOrder;

    #[LiveProp(writable: true)]
    public ?string $printed = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }


    #[LiveAction]
    public function save(): void
    {
        $this->denyAccessUnlessGranted('warehouse_update_order_print_count');

        $oldPrinted = $this->warehouseOrder->getPrinted();

        $this->warehouseOrder->setPrinted($this->printed);
        $this->entityManager->persist($this->warehouseOrder);
        $this->entityManager->flush();

        if ($oldPrinted != $this->printed) {
            $this->warehouseService->setAdminUser($this->getUser());
            $this->warehouseService->addWarehouseOrderLog($this->warehouseOrder, "Printed count updated to <b>" . $this->printed . "</b>");
        }
    }

}
