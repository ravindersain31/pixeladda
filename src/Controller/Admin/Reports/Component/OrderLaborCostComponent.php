<?php

namespace App\Controller\Admin\Reports\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Entity\Reports\DailyCogsReport;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "OrderLaborCostComponent",
    template: "admin/reports/components/order_labor_cost.html.twig"
)]
class OrderLaborCostComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Order $order = null;

    #[LiveProp(writable: true)]
    public ?string $laborCost = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    #[LiveAction]
    public function save(): void
    {
        $dailyCogsReport = $this->order;
        $dailyCogsReport->setLaborCost(floatval($this->laborCost));
        $this->entityManager->persist($dailyCogsReport);
        $this->entityManager->flush();
    }

}
