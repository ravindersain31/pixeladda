<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderLog;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseOrderCard",
    template: "admin/warehouse/components/order-card.html.twig"
)]
class OrderCardComponent extends AbstractController
{

    use DefaultActionTrait;

    #[LiveProp]
    public WarehouseOrder $warehouseOrder;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveAction]
    public function updateStatus(#[LiveArg] string $status): void
    {
        $this->denyAccessUnlessGranted('warehouse_update_order_print_status');

        $currentStatus = $this->warehouseOrder->getPrintStatus();
        $this->warehouseOrder->setPrintStatus($status);
        $this->entityManager->persist($this->warehouseOrder);
        $this->entityManager->flush();
        $newStatus = $this->warehouseOrder->getPrintStatus();

        if ($currentStatus !== $newStatus) {
            $oldStatus = isset(WarehouseOrderStatusEnum::STATUS[$currentStatus]) ? WarehouseOrderStatusEnum::STATUS[$currentStatus]['label'] : $currentStatus;
            $this->logChange('Status changed from <b>' . $oldStatus . '</b> to <b>' . WarehouseOrderStatusEnum::STATUS[$newStatus]['label'].'</b>');
        }
    }

    private function logChange(string $content): void
    {
        $log = new WarehouseOrderLog();
        $log->setOrder($this->warehouseOrder);
        $log->setLoggedBy($this->getUser());
        $log->setContent($content);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }


}
