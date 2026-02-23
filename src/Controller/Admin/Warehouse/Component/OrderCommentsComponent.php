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
    name: "AdminWarehouseOrderCommentsComponent",
    template: "admin/warehouse/components/order-comments.html.twig"
)]
class OrderCommentsComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?WarehouseOrder $warehouseOrder = null;

    #[LiveProp]
    public Order $order;

    #[LiveProp]
    public string $from = 'queue';

    #[LiveProp(writable: true)]
    public ?string $comments = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    #[LiveAction]
    public function save(): void
    {
        $this->warehouseService->setAdminUser($this->getUser());
        $warehouseOrder = $this->warehouseService->getWarehouseOrder($this->order, $this->warehouseOrder);
        $warehouseOrder->setComments($this->comments);
        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();
    }

}
