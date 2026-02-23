<?php

namespace App\Controller\Admin\Reports\Component;

use App\Controller\Cron\UpdateCogsReportController;
use App\Entity\Admin\Cogs\ShippingInvoiceFile;
use App\Entity\Order;
use App\Entity\Reports\DailyCogsReport;
use App\Entity\Reports\OrderCogsReport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "CogsDailyReportEditComponent",
    template: "admin/reports/components/cogs_daily_report_edit.html.twig"
)]
class CogsDailyReportEditComponent extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UpdateCogsReportController $updateCogsReport)
    {
    }

    #[LiveProp]
    public ?DailyCogsReport $dailyCogsReport = null;

    #[LiveProp(writable: true)]
    public array $orders = [];

    #[LiveProp(writable: true)]
    public float $totalPaymentLinkAmountReceived = 0;

    #[LiveProp(writable: true)]
    public float $totalSheetsUsed = 0;

    #[LiveProp(writable: true)]
    public float $totalWireStakeUsed = 0;

    #[LiveProp(writable: true)]
    public float $totalMaterialCost = 0;

    #[LiveProp(writable: true)]
    public float $totalShippingCost = 0;

    #[LiveProp(writable: true)]
    public float $totalShippingCostCSV = 0;

    #[LiveProp(writable: true)]
    public float $totalSales = 0;

    #[LiveProp(writable: true)]
    public float $totalPaidSales = 0;

    #[LiveProp(writable: true)]
    public float $paymentLinkAmount = 0;

    #[LiveProp(writable: true)]
    public float $totalRefunded = 0;

    #[LiveProp(writable: true)]
    public float $totalProfitAndLoss = 0;

    #[LiveProp(writable: true)]
    public float $pendingPayments = 0;

    #[LiveProp(writable: true)]
    public float $totalShippingAdjustments = 0;

    #[LiveProp(writable: true)]
    public float $totalShippingAmount = 0;

    #[LiveProp(writable: true)]
    public float $totalNetMargin = 0;

    #[LiveProp(writable: true)]
    public float $totalGrossMargin = 0;

    #[LiveProp(writable: true)]
    public int $totalOrders = 0;

    #[LiveProp(writable: true)]
    public float $totalLaborCost = 0;

    #[LiveProp(writable: true)]
    public float $totalAdsCost = 0;

    #[LiveProp(writable: true)]
    public bool $isEdit = false;

    #[LiveAction]
    public function resetOrderRow(#[LiveArg('index')] int $index): void
    {
        $orderId = $this->orders[$index]['orderId'] ?? null;

        if (!$orderId) {
            return;
        }

        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);

        if (!$order) {
            return;
        }

        $this->orders[$index] = $this->prepareOrderData($order, true);

        $orderCogsReport = $this->entityManager->getRepository(OrderCogsReport::class)->findOneBy([
            'relatedOrder' => $order,
            'dailyCogsReport' => $this->dailyCogsReport,
        ]);

        if ($orderCogsReport) {
            $this->entityManager->remove($orderCogsReport);
            $this->entityManager->flush();
        }
            
        $this->calculateAllTotals();
    }

    public function mount(array $orders, $dailyCogsReport): void
    {
        $this->dailyCogsReport = $dailyCogsReport;
        $this->totalOrders = count($orders);

        $this->orders = array_map(function(Order $order) {
            return $this->prepareOrderData($order);
        }, $orders);

        $this->calculateAllTotals();
    }

    private function prepareOrderData(Order $order, bool $ignoreCogsReport = false): array
    {
        $orderCogsReport = null;

        if (!$ignoreCogsReport) {
            $orderCogsReport = $this->entityManager->getRepository(OrderCogsReport::class)->findOneBy([
                'relatedOrder' => $order,
                'dailyCogsReport' => $this->dailyCogsReport,
            ]);
        }

        $materialCostBreakdown = $orderCogsReport?->getMaterialCostBreakdown() ?? $order->getMaterialCost();
        $totalMaterialCost   = (float) ($orderCogsReport?->getMaterialCost() ?? $order->getMaterialCost()['totalMaterialCost'] ?? 0);
        $laborCost           = (float) ($orderCogsReport?->getLaborCost() ?? $order->getTotalLaborCost() ?? 0);
        $refundedAmount      = (float) ($orderCogsReport?->getRefundedAmount() ?? $order->getRefundedAmount() ?? 0);
        $shippingCost        = (float) $order->getTotalShippingCost();
        $weightedAdsCost     = (float) $order->getWeightedAdsCost();
        $totalReceivedAmount = (float) $order->getTotalReceivedAmount();

        $profitAndLoss = round($totalReceivedAmount - (
            $refundedAmount + $shippingCost + $totalMaterialCost + $laborCost + $weightedAdsCost
        ), 2);

        $grossMargin = round($totalReceivedAmount - (
            $refundedAmount + $shippingCost + $totalMaterialCost + $laborCost
        ), 2);

        $grossMarginPercentage = $totalReceivedAmount > 0
            ? round(($grossMargin / $totalReceivedAmount) * 100, 2)
            : 0.0;

        $netMargin = $profitAndLoss;
        $netMarginPercentage = $totalReceivedAmount > 0
            ? round(($netMargin / $totalReceivedAmount) * 100, 2)
            : 0.0;


        return [
            'id' => $order->getId(),
            'orderId' => $order->getOrderId(),
            'status' => $order->getStatus(),
            'paymentStatus' => $order->getPaymentStatus(),
            'totalAmount' => $order->getTotalAmount(),
            'totalReceivedAmount' => $totalReceivedAmount,
            'paymentLinkAmountReceived' => $order->getPaymentLinkAmountReceived(),
            'materialCost' => $materialCostBreakdown,
            'totalMaterialCost' => round($totalMaterialCost, 2),
            'shippingCost' => $order->getShippingCost(),
            'shippingCosts' => $this->mapShippingCosts($order->getShippingCosts()),
            'laborCost' => round($laborCost, 2),
            'weightedAdsCost' => $weightedAdsCost,
            'TotalShippingCost' => $shippingCost,
            'refundedAmount' => round($refundedAmount, 2),
            'profitAndLoss' => round($profitAndLoss, 2),
            'grossMargin' => round($grossMargin, 2),
            'grossMarginPercentage' => $grossMarginPercentage,
            'netMargin' => round($netMargin, 2),
            'netMarginPercentage' => $netMarginPercentage,
            'isEdit' => $orderCogsReport?->isEdit() ?? false,
            'isReset' => $orderCogsReport?->isReset() ?? false,
        ];
        
    }

    private function mapShippingCosts(array $shippingCosts): array
    {
        return [
            'shippingCharges' => (float) ($shippingCosts['shippingCharges'] ?? 0),
            'shippingAdjustment' => (float) ($shippingCosts['shippingAdjustment'] ?? 0),
            'shippingTotal' => (float) ($shippingCosts['shippingTotal'] ?? 0),
            'shippingInvoiceFile' => $this->mapInvoiceFile($shippingCosts['shippingInvoiceFile'] ?? null),
        ];
    }

    private function mapInvoiceFile($invoiceFile): ?array
    {
        if (!$invoiceFile instanceof ShippingInvoiceFile) {
            return null;
        }

        return [
            'id' => $invoiceFile->getId(),
            'generatedAt' => $invoiceFile->getGeneratedAt()?->format('Y-m-d'),
        ];
    }

    private function calculateAllTotals(): void
    {
        $this->totalSales = $this->sumField('totalAmount');
        $this->totalPaidSales = $this->sumField('totalReceivedAmount');
        $this->totalPaymentLinkAmountReceived = $this->sumField('paymentLinkAmountReceived');
        
        $this->totalSheetsUsed = array_sum(array_map(
            fn($order) => floatval($order['materialCost']['sheets']['fullSheetsUsed'] ?? 0),
            $this->orders
        ));

        $this->totalMaterialCost = array_sum(array_map(
            fn($order) => floatval($order['totalMaterialCost'] ?? 0),
            $this->orders
        ));
        
        $this->totalShippingCost = array_sum(array_map(
            fn($order) => floatval($order['shippingCost'] ?? 0),
            $this->orders
        ));

        $this->totalWireStakeUsed = array_sum(array_map(
            fn($order) => floatval($order['materialCost']['wireStakeUsed'] ?? 0),
            $this->orders
        ));

        $this->totalRefunded = $this->sumField('refundedAmount');
        $this->totalProfitAndLoss = $this->sumField('profitAndLoss');
        $this->totalGrossMargin = $this->sumField('grossMargin');
        $this->totalNetMargin = $this->sumField('netMargin');
        $this->totalLaborCost = $this->sumField('laborCost');
        $this->totalAdsCost = $this->sumField('weightedAdsCost');
        $this->pendingPayments = $this->totalSales - $this->totalPaidSales;

        $this->syncDailyCogsReportFromTotals();
    }

    private function sumField(string $field): float
    {
        return array_sum(array_map(fn($row) => (float) ($row[$field] ?? 0), $this->orders));
    }

    #[LiveAction]
    public function updateOrder(
        #[LiveArg('index')] int $index,
        #[LiveArg('field')] string $field,
    ): void {
        $order = &$this->orders[$index];

        if (isset($order['isReset']) && $order['isReset'] === true) {
            $order['isReset'] = false;
        }

        if (str_starts_with($field, 'totalMaterialCost')) {
            $this->handleMaterialTotalUpdate($order);
        }
        $this->calculateMargins($order);
        $this->saveOrUpdateOrderCogsReport($order);
        
        $this->calculateAllTotals();
    }

    private function handleMaterialTotalUpdate(array &$order): void
    {
        if (!isset($order['materialCost'])) {
            return;
        }

        $material = &$order['materialCost'];

        $original = (float) $material['totalMaterialCost'];
        $updated = (float) $order['totalMaterialCost'];

        if ($original !== $updated) {
            $material['updateTotalMaterialCost'] = $updated;
            $this->isEdit = true;
        } else {
            $material['totalMaterialCost'] = $updated;
            unset($material['updateTotalMaterialCost']);
            $this->isEdit = false;
        }
    }
    
    private function saveOrUpdateOrderCogsReport(array $order): void
    {
        $orderOriginal = $this->entityManager->getRepository(Order::class)->findOneBy(["orderId" => $order['orderId']]);

        $orderCogsReport = $this->entityManager->getRepository(OrderCogsReport::class)->findOneBy([
            'relatedOrder' => $orderOriginal,
            'dailyCogsReport' => $this->dailyCogsReport,
        ]);

        $originalLaborCost = round((float) $orderOriginal->getLaborCost(), 2);
        $updatedLaborCost = round((float) $order['laborCost'], 2);

        $originalRefundedAmount = round((float) $orderOriginal->getRefundedAmount(), 2);  
        $updatedRefundedAmount = round((float) $order['refundedAmount'], 2); 

        $this->isEdit = $this->isEdit || ($originalLaborCost != $updatedLaborCost) || ($originalRefundedAmount != $updatedRefundedAmount);

        if (!$orderCogsReport) {
            $orderCogsReport = new OrderCogsReport();
            $orderCogsReport->setRelatedOrder($orderOriginal);
            $orderCogsReport->setDailyCogsReport($this->dailyCogsReport);
        }

        $orderCogsReport->setMaterialCost((float) $order['totalMaterialCost']);
        $orderCogsReport->setMaterialCostBreakdown($order['materialCost']);
        $orderCogsReport->setLaborCost((float) $order['laborCost']);
        $orderCogsReport->setRefundedAmount((float) $order['refundedAmount']);
        $orderCogsReport->setIsEdit((bool) $this->isEdit);
        $orderCogsReport->setIsReset((bool) $order['isReset']);

        $this->entityManager->persist($orderCogsReport);
        $this->entityManager->flush();
    }

    private function calculateMargins(array &$order): void
    {
        $totalMaterialCost = (float) ($order['totalMaterialCost'] ?? 0);
        $laborCost = (float) ($order['laborCost'] ?? 0);
        $refundedAmount = (float) ($order['refundedAmount'] ?? 0);
        $shippingCost = (float) ($order['TotalShippingCost'] ?? 0);
        $adsCost = (float) ($order['weightedAdsCost'] ?? 0);
        $receivedAmount = (float) ($order['totalReceivedAmount'] ?? 0.0);

        $profitAndLoss = $receivedAmount - ($refundedAmount + $shippingCost + $totalMaterialCost + $laborCost + $adsCost);
        $order['profitAndLoss'] = round($profitAndLoss, 2);

        $grossMargin = $receivedAmount - ($refundedAmount + $shippingCost + $totalMaterialCost + $laborCost);
        $order['grossMargin'] = round($grossMargin, 2);
        $order['grossMarginPercentage'] = $receivedAmount > 0
            ? round(($grossMargin / $receivedAmount) * 100, 2)
            : 0.0;

        $order['netMargin'] = round($profitAndLoss, 2);
        $order['netMarginPercentage'] = $receivedAmount > 0
            ? round(($profitAndLoss / $receivedAmount) * 100, 2)
            : 0.0;
    }

    private function syncDailyCogsReportFromTotals(): void
    {
        if (!$this->dailyCogsReport) {
            return;
        }

        $report = $this->dailyCogsReport;
        $updated = false;

        if ($report->getOriginalMaterialCost() === null) {
            $report->setOriginalMaterialCost((float) $report->getMaterialCost());
            $updated = true;
        }

        if ($report->getOriginalLaborCost() === null) {
            $report->setOriginalLaborCost((float) $report->getLaborCost());
            $updated = true;
        }

        if ($report->getOriginalRefundedAmount() === null) {
            $report->setOriginalRefundedAmount((float) $report->getTotalRefundedAmount());
            $updated = true;
        }

        $calculatedMaterialCost = round($this->totalMaterialCost, 2);
        if ((float)$report->getMaterialCost() !== $calculatedMaterialCost) {
            $report->setMaterialCost($calculatedMaterialCost);
            $updated = true;
        }

        $calculatedLaborCost = round($this->totalLaborCost, 2);
        if ((float)$report->getLaborCost() !== $calculatedLaborCost) {
            $report->setLaborCost($calculatedLaborCost);
            $updated = true;
        }

        $calculatedRefundedAmount = round($this->totalRefunded, 2);
        if ((float)$report->getTotalRefundedAmount() !== $calculatedRefundedAmount) {
            $report->setTotalRefundedAmount($calculatedRefundedAmount);
            $updated = true;
        }

        if ($updated) {
            $this->entityManager->persist($report);
            $this->entityManager->flush();
        }
    }

    private function updateReportByDate(): void
    {
        $date = $this->dailyCogsReport->getDate();
        $immutableDate = $date instanceof \DateTimeImmutable
            ? $date
            : \DateTimeImmutable::createFromMutable($date);

        $this->updateCogsReport->updateDailyCogsReport($immutableDate);
    }
}