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
    name: "DailyReportLaborCostComponent",
    template: "admin/reports/components/day_labor_cost.html.twig"
)]
class DailyReportLaborCostComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?DailyCogsReport $dailyCogsReport = null;

    #[LiveProp(writable: true)]
    public ?string $laborCost = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    #[LiveAction]
    public function save(): void
    {
        $dailyCogsReport = $this->dailyCogsReport;
        $dailyCogsReport->setLaborCost(floatval($this->laborCost));
        $this->entityManager->persist($dailyCogsReport);
        $this->entityManager->flush();

        $date = $dailyCogsReport->getDate();
        $startOfDay = new \DateTimeImmutable($date->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($date->format('Y-m-d 23:59:59'));

        $orders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)->getResult();

        // Calculate total sheets used for all orders
        $totalSheetsUsed = 0;
        foreach ($orders as $order) {
            $materialCost = $order->getMaterialCost();
            $sheetsSingleSidedPrint = $materialCost['sheetsSingleSidedPrint'] ?? 0;
            $sheetsDoubleSidedPrint = $materialCost['sheetsDoubleSidedPrint'] ?? 0;
            $totalSheetsUsed += $sheetsSingleSidedPrint + $sheetsDoubleSidedPrint;
        }

        // Avoid division by zero
        if ($totalSheetsUsed <= 0) {
            return;
        }

        // Distribute labor cost proportionally
        $totalLaborCost = floatval($this->laborCost);

        /** @var Order $order */
        foreach ($orders as $order) {
            $materialCost = $order->getMaterialCost();
            $sheetsSingleSidedPrint = $materialCost['sheetsSingleSidedPrint'] ?? 0;
            $sheetsDoubleSidedPrint = $materialCost['sheetsDoubleSidedPrint'] ?? 0;

            $sheetsUsed = $sheetsSingleSidedPrint + $sheetsDoubleSidedPrint;
            $weightedLaborCost = ($sheetsUsed / $totalSheetsUsed) * $totalLaborCost;

            $order->setLaborCost($weightedLaborCost);
            $this->entityManager->persist($order);
        }
        $this->entityManager->flush();
    }

}
