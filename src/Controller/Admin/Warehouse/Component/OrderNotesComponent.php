<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseOrderNotesComponent",
    template: "admin/warehouse/components/order-notes.html.twig"
)]
class OrderNotesComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?WarehouseOrder $warehouseOrder = null;

    #[LiveProp]
    public Order $order;

    #[LiveProp(writable: true)]
    public ?string $notes = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    #[LiveAction]
    public function save(): void
    {
        $this->warehouseService->setAdminUser($this->getUser());
        $warehouseOrder = $this->warehouseService->getWarehouseOrder($this->order, $this->warehouseOrder);
        $warehouseOrder->setNotes($this->notes);
        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();
    }

}
