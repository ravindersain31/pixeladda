<?php

namespace App\Controller\Admin\Reports;

use App\Entity\Admin\Cogs\ShippingInvoiceFile;
use App\Entity\Order;
use App\Entity\Reports\DailyCogsReport;
use App\Entity\Reports\MonthlyCogsReport;
use App\Entity\Reports\OrderCogsReport;
use App\Enum\OrderStatusEnum;
use App\Form\Admin\Reports\CogsDailyDataUpdateType;
use App\Form\Admin\Reports\CogsMonthlyDataUpdateType;
use App\Repository\Reports\MonthlyCogsReportRepository;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/cogs')]
class CogsController extends AbstractController
{
    #[Route('/', name: 'report_cogs')]
    public function index(Request $request, MonthlyCogsReportRepository $repository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $months = $repository->findBy([], ['date' => 'DESC']);
        return $this->render('admin/reports/cogs.html.twig', [
            'months' => $months,
        ]);
    }

    #[Route('/month/{month}/view', name: 'report_cogs_view')]
    public function monthView(Request $request, MonthlyCogsReport $month, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $dailyCogsReports = $entityManager->getRepository(DailyCogsReport::class)->findBy(['month' => $month], ['date' => 'ASC']);
        $shippingInvoices = $entityManager->getRepository(ShippingInvoiceFile::class)->findBy([], ['id' => 'DESC']);

        return $this->render('admin/reports/cogs_month_view.html.twig', [
            'month' => $month,
            'dailyCogsReports' => $dailyCogsReports,
            'shippingInvoices' => $shippingInvoices
        ]);
    }

    #[Route('/month/{month}/day/{day}', name: 'report_cogs_day_view')]
    public function dayView(Request $request, MonthlyCogsReport $month, DailyCogsReport $day, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $dayDate = new \DateTimeImmutable($day->getDate()->format('Y-m-d'));
        $startOfDay = $dayDate->setTime(0, 0, 0);
        $endOfDay = $dayDate->setTime(23, 59, 59);
        $orders = $entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)->getResult();
        $cancelledOrders = $entityManager->getRepository(Order::class)->filterOrder(status: [OrderStatusEnum::CANCELLED], fromDate: $startOfDay, endDate: $endOfDay, canceledOrders: true)->getResult();
        return $this->render('admin/reports/cogs_day_view.html.twig', [
            'month' => $month,
            'day' => $day,
            'orders' => $orders,
            'cancelledOrders' => $cancelledOrders
        ]);
    }

    #[Route('/month/{month}/export', name: 'report_cogs_export')]
    public function monthExport(Request $request, MonthlyCogsReport $month, EntityManagerInterface $entityManager, AppExtension $appExtension): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $dailyCogsReports = $entityManager->getRepository(DailyCogsReport::class)->findBy(['month' => $month], ['date' => 'ASC']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Date',
            'Gross Sales',
            'Paid Sales',
            'Sheets Breakdown',
            'Sheets',
            'Stakes Breakdown',
            'Stakes',
            'Cost Breakdown',
            'Material Cost',
            'Warehouse Labor',
            'Shipping Charges',
            'Shipping Adjustments',
            'Shipping Total',
            'Google Ads',
            'Bing Ads',
            'Facebook Ads',
            'Total Ads Cost',
            'Total Cost',
            'Partial Refunds',
            'Total Partial Refunds',
            'Cancelled Orders',
            'Cancelled Sales',
            'AOV',
            'Orders',
            'Ratio',
            'Profit/Loss',
            'Gross Margin',
            'Net Margin'
        ];
        $sheet->fromArray([$header], null, 'A1');

        $totalSales = 0;
        $totalPaidSales = 0;
        $totalSheetUsed = 0;
        $totalWireStakeUsed = 0;
        $totalMaterialCost = 0;
        $totalLaborCost = 0;
        $totalShippingCharges = 0;
        $totalShippingAdjustment = 0;
        $totalShippingCost = 0;
        $totalGoogleAdsCost = 0;
        $totalBingAdsCost = 0;
        $totalFacebookAdsCost = 0;
        $totalCost = 0;
        $totalRefunds = 0;
        $totalRefundedOrders = 0;
        $totalProfitLoss = 0;
        $totalOrders = 0;
        $totalCancelledOrders = 0;
        $totalCancelledSales = 0;
        $totalPaidOrders = 0;
        $totalGrossMargin = 0;
        $totalNetMargin = 0;
        $marginsCount = 0;

        $rowIndex = 2;
        foreach ($dailyCogsReports as $day) {
            $materialBreakdown = $day->getMaterialCostBreakdown();
            $shippingBreakdown = $day->getShippingCostBreakdown();
            $sheetsData = $materialBreakdown['sheets']['sheetsData'] ?? [];
            $wireStakesData = $materialBreakdown['wireStakes'] ?? [];
            $materialCostBreakdown = $materialBreakdown['materialCostBreakdown'] ?? [];
            $formattedSheetsData = [];
            $formattedWireStakes = [];
            $formattedMaterialBreakdown = "-";

            $formattedSheetsData = array_filter(array_map(
                fn($name, $sheet) => $sheet > 0 ? str_replace('fullSheetsUsed', 'Full Sheet', $name) . ': ' . number_format($sheet, 2) : null,
                array_keys($sheetsData),
                $sheetsData
            ));            

            $formattedWireStakes = array_filter(array_map(
                fn($name, $stake) => $stake > 0 ? $appExtension->getAddonsFrameDisplayText($name) . ": $stake" : null,
                array_keys($wireStakesData),
                $wireStakesData
            ));

            $formattedSheetsDataString = $formattedSheetsData ? implode("\n", $formattedSheetsData) : '-';
            $formattedWireStakesString = $formattedWireStakes ? implode("\n", $formattedWireStakes) : '-';

            if (!empty($materialCostBreakdown)) {
                $formattedMaterialBreakdown = implode("\n", array_map(
                    fn($entry) => "{$entry['item']} | QTY: {$entry['quantity']} | Unit Cost: {$entry['unitCost']} | Total: {$entry['totalCost']}",
                    array_filter($materialCostBreakdown, fn($entry) => !empty($entry['item']) && $entry['item'] !== "Total") 
                ));
            
                $lastEntry = end($materialCostBreakdown);
                if (!empty($lastEntry['totalCost'])) {
                    $formattedMaterialBreakdown .= "\n------------------------\nTotal Cost: " . number_format($lastEntry['totalCost'], 2);
                }

                $materialCost = $day->getMaterialCost();
                $originalCost = $day->getOriginalMaterialCost();

                if (
                    $materialCost !== null &&
                    $originalCost !== null &&
                    number_format((float) $materialCost, 2) != number_format((float) $originalCost, 2)
                ) {
                    $formattedMaterialBreakdown .= "\nUpdated Material Cost: $" . number_format((float) $materialCost, 2);
                }
            }
            
            $sheet->setCellValue('A' . $rowIndex, $day->getDate()->format('Y-m-d'));
            $sheet->setCellValue('B' . $rowIndex, number_format($day->getTotalSales(), 2));
            $sheet->setCellValue('C' . $rowIndex, number_format($day->getTotalPaidSales(), 2));
            $sheet->setCellValue('D' . $rowIndex, $formattedSheetsDataString); 
            $sheet->setCellValue('E' . $rowIndex, number_format($materialBreakdown['totalSheetsUsed'] ?? 0, 2));
            $sheet->setCellValue('F' . $rowIndex, $formattedWireStakesString); 
            $sheet->setCellValue('G' . $rowIndex, number_format($materialBreakdown['totalWireStakeUsed'] ?? 0, 2)); 
            $sheet->setCellValue('H' . $rowIndex, $formattedMaterialBreakdown);
            $sheet->setCellValue('I' . $rowIndex, number_format($day->getMaterialCost(), 2));
            $sheet->setCellValue('J' . $rowIndex, number_format($day->getTotalLaborCost(), 2)); 
            $sheet->setCellValue('K' . $rowIndex, number_format($shippingBreakdown['shippingCharges'] ?? 0, 2));
            $sheet->setCellValue('L' . $rowIndex, number_format($shippingBreakdown['shippingAdjustment'] ?? 0, 2));
            $sheet->setCellValue('M' . $rowIndex, number_format($day->getTotalShippingCost(), 2));
            $sheet->setCellValue('N' . $rowIndex, number_format($day->getGoogleAdsSpent(), 2));
            $sheet->setCellValue('O' . $rowIndex, number_format($day->getBingAdsSpent(), 2));
            $sheet->setCellValue('P' . $rowIndex, number_format($day->getFacebookAdsSpent(), 2));
            $sheet->setCellValue('Q' . $rowIndex, number_format($day->getGoogleAdsSpent() + $day->getBingAdsSpent() + $day->getFacebookAdsSpent(), 2));
            $sheet->setCellValue('R' . $rowIndex, number_format($day->getTotalCost(), 2));
            $sheet->setCellValue('S' . $rowIndex, number_format($day->getTotalRefundedAmount(), 2)); 
            $sheet->setCellValue('T' . $rowIndex, count($day->getRefundedOrders()));
            $sheet->setCellValue('U' . $rowIndex, count($day->getCancelledOrders()));
            $sheet->setCellValue('V' . $rowIndex, number_format($day->getCancelledSales(), 2));
            
            $avgOrder = $day->getTotalPaidOrders() > 0 ? $day->getTotalPaidSales() / $day->getTotalPaidOrders() : 0;
            $sheet->setCellValue('W' . $rowIndex, number_format($avgOrder, 2)); 
            $sheet->setCellValue('X' . $rowIndex, $day->getTotalOrders() . '/' . $day->getTotalPaidOrders());
            
            $ratio = $day->getTotalAdsCost() > 0 ? $day->getTotalPaidSales() / $day->getTotalAdsCost() : 0;
            $sheet->setCellValue('Y' . $rowIndex, number_format($ratio, 2)); 
            $sheet->setCellValue('Z' . $rowIndex, number_format($day->profitAndLoss(), 2));
            $sheet->setCellValue('AA' . $rowIndex, number_format($day->getGrossMargin(), 2));
            $sheet->setCellValue('AB' . $rowIndex, number_format($day->getNetMargin(), 2));                
            $rowIndex++;

            $totalSales += $day->getTotalSales();
            $totalPaidSales += $day->getTotalPaidSales();
            $totalSheetUsed += $materialBreakdown['totalSheetsUsed'] ?? 0;
            $totalWireStakeUsed += $materialBreakdown['totalWireStakeUsed'] ?? 0;
            $totalMaterialCost += $day->getMaterialCost();
            $totalLaborCost += $day->getTotalLaborCost();
            $totalShippingCharges += $shippingBreakdown['shippingCharges'] ?? 0;
            $totalShippingAdjustment += $shippingBreakdown['shippingAdjustment'] ?? 0;
            $totalShippingCost += $day->getTotalShippingCost();
            $totalGoogleAdsCost += $day->getGoogleAdsSpent();
            $totalBingAdsCost += $day->getBingAdsSpent();
            $totalFacebookAdsCost += $day->getFacebookAdsSpent();
            $totalCost += $day->getTotalCost();
            $totalRefunds += $day->getTotalRefundedAmount();
            $totalRefundedOrders += count($day->getRefundedOrders());
            $totalCancelledOrders += count($day->getCancelledOrders());
            $totalCancelledSales += $day->getCancelledSales();
            $totalProfitLoss += $day->profitAndLoss();
            $totalOrders += $day->getTotalOrders();
            $totalPaidOrders += $day->getTotalPaidOrders();
            $totalGrossMargin += $day->getGrossMargin();
            $totalNetMargin += $day->getNetMargin();

            if( $totalOrders > 0){
                $marginsCount = $marginsCount + 1;
            } 
        }

        $totalAdsCost = $totalGoogleAdsCost + $totalBingAdsCost + $totalFacebookAdsCost;

        $sheet->setCellValue('A' . $rowIndex, 'Total');
        $sheet->setCellValue('B' . $rowIndex, number_format($totalSales, 2));
        $sheet->setCellValue('C' . $rowIndex, number_format($totalPaidSales, 2));
        $sheet->setCellValue('D' . $rowIndex, '-');
        $sheet->setCellValue('E' . $rowIndex, number_format($totalSheetUsed, 2));
        $sheet->setCellValue('F' . $rowIndex, '-');
        $sheet->setCellValue('G' . $rowIndex, number_format($totalWireStakeUsed, 2));
        $sheet->setCellValue('H' . $rowIndex, '-');
        $sheet->setCellValue('I' . $rowIndex, number_format($totalMaterialCost, 2));
        $sheet->setCellValue('J' . $rowIndex, number_format($totalLaborCost, 2));
        $sheet->setCellValue('K' . $rowIndex, number_format($totalShippingCharges, 2));
        $sheet->setCellValue('L' . $rowIndex, number_format($totalShippingAdjustment, 2));
        $sheet->setCellValue('M' . $rowIndex, number_format($totalShippingCost, 2));
        $sheet->setCellValue('N' . $rowIndex, number_format($totalGoogleAdsCost, 2));
        $sheet->setCellValue('O' . $rowIndex, number_format($totalBingAdsCost, 2));
        $sheet->setCellValue('P' . $rowIndex, number_format($totalFacebookAdsCost, 2));
        $sheet->setCellValue('Q' . $rowIndex, number_format($totalAdsCost, 2));
        $sheet->setCellValue('R' . $rowIndex, number_format($totalCost, 2));
        $sheet->setCellValue('S' . $rowIndex, number_format($totalRefunds, 2));
        $sheet->setCellValue('T' . $rowIndex, number_format($totalRefundedOrders, 2));
        $sheet->setCellValue('U' . $rowIndex, number_format($totalCancelledOrders, 2)); 
        $sheet->setCellValue('V' . $rowIndex, number_format($totalCancelledSales, 2));
        $sheet->setCellValue('W' . $rowIndex, number_format($appExtension->divide($totalPaidSales, $totalPaidOrders), 2));
        $sheet->setCellValue('X' . $rowIndex, number_format($appExtension->divide($totalOrders, $marginsCount), 1) . '/' . number_format($appExtension->divide($totalPaidOrders, $marginsCount), 1));
        $sheet->setCellValue('Y' . $rowIndex, number_format($appExtension->divide($totalPaidSales, $totalAdsCost), 2));
        $sheet->setCellValue('Z' . $rowIndex, number_format($totalProfitLoss, 2));

        $grossMarginPercentage = ($totalPaidSales > 0) ? ($totalGrossMargin / $totalPaidSales) * 100 : 0;
        $sheet->setCellValue('AA' . $rowIndex, number_format($totalGrossMargin, 2) . ' (' . number_format($grossMarginPercentage, 2) . '%' . ')');

        $netMarginPercentage = ($totalPaidSales > 0) ? ($totalNetMargin / $totalPaidSales) * 100 : 0;
        $sheet->setCellValue('AB' . $rowIndex, number_format($totalNetMargin, 2) .' (' . number_format($netMarginPercentage, 2) . '%' . ')');

        $writer = new Csv($spreadsheet);

        $fileName = 'cogs_monthly_report_' . $month->getDate()->format('Y-m') . '.csv';

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    #[Route('/month/{month}/day/{day}/export', name: 'report_cogs_day_export')]
    public function dayExport(Request $request, MonthlyCogsReport $month, DailyCogsReport $day, EntityManagerInterface $entityManager, AppExtension $appExtension): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $dayDate = new \DateTimeImmutable($day->getDate()->format('Y-m-d'));
        $startOfDay = $dayDate->setTime(0, 0, 0);
        $endOfDay = $dayDate->setTime(23, 59, 59);
        $orders = $entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)->getResult();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Order ID',
            'Status',
            'Pay Status',
            'Order Total',
            'Received Amount',
            'Received Payment Links Amount',
            'Sheets Breakdown',
            'Sheets',
            'Stakes Breakdown',
            'Wire Stake',
            'Material Cost Breakdown',
            'Material Cost',
            'Warehouse Labor',
            'Ads Cost',
            'Shipping Cost',
            'Shipping Adjustments',
            'Shipping Total',
            'Refunded',
            'Profit/Loss',
            'Gross Margin',
            'Net Margin',
        ];
        $sheet->fromArray([$header], null, 'A1');

        $totalSheetsUsed = 0;
        $totalWireStakeUsed = 0;
        $totalMaterialCost = 0;
        $totalShippingCost = 0;
        $totalSales = 0;
        $totalPaidSales = 0;
        $totalReceivedAmount = 0;
        $totalPaymentLinkAmount = 0;
        $totalPaymentLinkAmountReceived = 0;
        $totalRefunded = 0;
        $totalProfitAndLoss = 0;
        $pendingPayments = 0;
        $totalShippingAdjustments = 0;
        $totalShippingAmount = 0;
        $totalNetMargin = 0;
        $totalGrossMargin = 0;
        $totalLaborCost = 0;
        $totalAdsCost = 0;

        $rowIndex = 2;
        foreach ($orders as $order) {
            $orderCogsReport = $entityManager->getRepository(OrderCogsReport::class)->findOneBy([
                'relatedOrder' => $order,
            ]);

            $materialCost = $orderCogsReport?->getMaterialCostBreakdown() ?? $order->getMaterialCost();
            $orderMaterialCost   = (float) ($orderCogsReport?->getMaterialCost() ?? $order->getMaterialCost()['totalMaterialCost'] ?? 0);
            $laborCost           = (float) ($orderCogsReport?->getLaborCost() ?? $order->getTotalLaborCost() ?? 0);
            $refundedAmount      = (float) ($orderCogsReport?->getRefundedAmount() ?? $order->getRefundedAmount() ?? 0);

            $shippingCosts = $order->getShippingCosts();
            $sheetsData = $materialCost['sheets'] ?? [];
            $wireStakesData = $materialCost['wireStakes'] ?? [];
            $materialCostBreakdown = $materialCost['materialCostBreakdown'] ?? [];
            $formattedSheetsData = [];
            $formattedWireStakes = [];
            $formattedMaterialBreakdown = "-";
            $formattedShippingCost = "-";

            $formattedSheetsData = array_filter(array_map(
                fn($name, $sheet) => $sheet > 0 ? str_replace('fullSheetsUsed', 'Full Sheet', $name) . ': ' . number_format($sheet, 2) : null,
                array_keys($sheetsData),
                $sheetsData
            ));
            
            $formattedWireStakes = array_filter(array_map(
                fn($name, $stake) => $stake > 0 ? $appExtension->getAddonsFrameDisplayText($name) . ": $stake" : null,
                array_keys($wireStakesData),
                $wireStakesData
            ));

            if (!empty($materialCostBreakdown)) {
                $formattedMaterialBreakdown = implode("\n", array_map(
                    fn($entry) => "{$entry['item']} | QTY: {$entry['quantity']} | Unit Cost: {$entry['unitCost']} | Total: {$entry['totalCost']}",
                    array_filter($materialCostBreakdown, fn($entry) => !empty($entry['item']) && $entry['item'] !== "Total") 
                ));
            
                $lastEntry = end($materialCostBreakdown);
                if (!empty($lastEntry['totalCost'])) {
                    $formattedMaterialBreakdown .= "\n------------------------\nTotal Cost: " . number_format($lastEntry['totalCost'], 2);
                }

                if (isset($materialCost['updateTotalMaterialCost']) && $materialCost['updateTotalMaterialCost'] != '') {
                    $formattedMaterialBreakdown .= "\n------------------------\nUpdated Material Cost: " . number_format($materialCost['updateTotalMaterialCost'], 2);
                }
            }
           
            if($order->getShippingCost()){
                if (!empty($shippingCosts['shippingCharges'])) {
                    $generatedAt = $shippingCosts['shippingInvoiceFile']?->getGeneratedAt()?->format('m/d/Y') ?? '';
                    $formattedShippingCost = "CSV: " . number_format($shippingCosts['shippingCharges'], 2) . "\n" . $generatedAt;
                }else{
                    $formattedShippingCost = "EP: " . number_format($order->getShippingCost(), 2);
                }
            }
   
            $formattedSheetsDataString = $formattedSheetsData ? implode("\n", $formattedSheetsData) : '-';
            $formattedWireStakesString = $formattedWireStakes ? implode("\n", $formattedWireStakes) : '-';
            
            $sheet->setCellValue('A' . $rowIndex, $order->getOrderId());
            $sheet->setCellValue('B' . $rowIndex, strip_tags($appExtension->badgeOrderStatus($order->getStatus(), true)));
            $sheet->setCellValue('C' . $rowIndex, strip_tags($appExtension->badgePaymentStatus($order->getPaymentStatus())));
            $sheet->setCellValue('D' . $rowIndex, number_format($order->getTotalAmount(), 2)); 
            $sheet->setCellValue('E' . $rowIndex, number_format($order->getTotalReceivedAmount() ?? 0, 2));
            $sheet->setCellValue('F' . $rowIndex, number_format($order->getPaymentLinkAmountReceived() ?? 0, 2)); 
            $sheet->setCellValue('G' . $rowIndex, $formattedSheetsDataString); 
            $sheet->setCellValue('H' . $rowIndex, number_format($materialCost['totalSheetsUsed'] ?? 0, 2));
            $sheet->setCellValue('I' . $rowIndex, $formattedWireStakesString); 
            $sheet->setCellValue('J' . $rowIndex, number_format($materialCost['wireStakeUsed'] ?? 0, 2));
            $sheet->setCellValue('K' . $rowIndex, $formattedMaterialBreakdown); 
            $sheet->setCellValue('L' . $rowIndex, number_format($orderMaterialCost ?? 0, 2));
            $sheet->setCellValue('M' . $rowIndex, number_format($laborCost, 2));
            $sheet->setCellValue('N' . $rowIndex, number_format($order->getWeightedAdsCost(), 2));
            $sheet->setCellValue('O' . $rowIndex, $formattedShippingCost);
            $sheet->setCellValue('P' . $rowIndex, number_format($shippingCosts['shippingAdjustment'] ?? 0, 2));
            $sheet->setCellValue('Q' . $rowIndex, number_format($shippingCosts['shippingTotal'] ?? 0, 2));
            $sheet->setCellValue('R' . $rowIndex, number_format($refundedAmount, 2));
            $sheet->setCellValue('S' . $rowIndex, number_format($order->getProfitAndLoss(), 2));             
            $sheet->setCellValue('T' . $rowIndex, number_format($order->getGrossMargin(), 2) . "\n(" . number_format($order->getGrossMarginPercentage(), 2) . "%) ");
            $sheet->setCellValue('U' . $rowIndex, number_format($order->getNetMargin(), 2) . "\n(" . number_format($order->getNetMarginPercentage(), 2) . "%) ");
            $rowIndex++;

            $totalSheetsUsed += $materialCost['totalSheetsUsed'] ?? 0;
            $totalWireStakeUsed += $materialCost['wireStakeUsed'] ?? 0;
            $totalMaterialCost += $orderMaterialCost ?? 0;
            $totalShippingAdjustments += $shippingCosts['shippingAdjustment'] ?? 0;
            $totalSales += $order->getTotalAmount();
            $totalRefunded += $refundedAmount;
            $totalProfitAndLoss += $order->getProfitAndLoss();
            $totalNetMargin += $order->getNetMargin();
            $totalGrossMargin += $order->getGrossMargin();
        
            if (!in_array($order->getPaymentStatus(), ['COMPLETED', 'PARTIALLY_REFUNDED'])) {
                $pendingPayments += ($order->getTotalAmount() - $order->getTotalReceivedAmount());
            }
        
            $totalShippingCost += $order->getTotalShippingCharges();
            $totalShippingAmount += $order->getTotalShippingCost();
            $totalAdsCost += $order->getWeightedAdsCost();
            $totalLaborCost += $laborCost;
            $totalReceivedAmount += $order->getTotalReceivedAmount();
            $totalPaymentLinkAmount += $order->getPaymentLinkAmount();
            $totalPaymentLinkAmountReceived += $order->getPaymentLinkAmountReceived();
        }

        $totalPaidSales = $totalReceivedAmount;
        $totalGrossMargin = $totalPaidSales - ($totalShippingAmount + $totalRefunded + $totalMaterialCost + $totalLaborCost);
        $totalNetMargin = $totalPaidSales - ($totalShippingAmount + $totalRefunded + $totalMaterialCost + $totalLaborCost + $totalAdsCost);

        $sheet->setCellValue('A' . $rowIndex, '');
        $sheet->setCellValue('B' . $rowIndex, '');
        $sheet->setCellValue('C' . $rowIndex, 'Total');
        $sheet->setCellValue('D' . $rowIndex, number_format($totalSales, 2));
        $sheet->setCellValue('E' . $rowIndex, number_format($totalReceivedAmount, 2));
        $sheet->setCellValue('F' . $rowIndex, number_format($totalPaymentLinkAmountReceived, 2));
        $sheet->setCellValue('G' . $rowIndex, '-');
        $sheet->setCellValue('H' . $rowIndex, number_format($totalSheetsUsed, 2));
        $sheet->setCellValue('I' . $rowIndex, '-');
        $sheet->setCellValue('J' . $rowIndex, number_format($totalWireStakeUsed, 2));
        $sheet->setCellValue('K' . $rowIndex, '-');
        $sheet->setCellValue('L' . $rowIndex, number_format($totalMaterialCost, 2));
        $sheet->setCellValue('M' . $rowIndex, number_format($totalLaborCost, 2));
        $sheet->setCellValue('N' . $rowIndex, number_format($totalAdsCost, 2));
        $sheet->setCellValue('O' . $rowIndex, number_format($totalShippingCost, 2));
        $sheet->setCellValue('P' . $rowIndex, number_format($totalShippingAdjustments, 2));
        $sheet->setCellValue('Q' . $rowIndex, number_format($totalShippingAmount, 2));
        $sheet->setCellValue('R' . $rowIndex, number_format($totalRefunded, 2));
        $sheet->setCellValue('S' . $rowIndex, number_format($totalProfitAndLoss, 2));

        $grossMarginPercentage = ($totalPaidSales > 0) ? ($totalGrossMargin / $totalPaidSales) * 100 : 0;
        $sheet->setCellValue('T' . $rowIndex, number_format($totalGrossMargin, 2) . "\n(" . number_format($grossMarginPercentage, 2) . "%)");

        $netMarginPercentage = ($totalPaidSales > 0) ? ($totalNetMargin / $totalPaidSales) * 100 : 0;
        $sheet->setCellValue('U' . $rowIndex, number_format($totalNetMargin, 2) . "\n(" . number_format($netMarginPercentage, 2) . "%)");

        $writer = new Csv($spreadsheet);

        $fileName = 'cogs_daily_report_' . $day->getDate()->format('Y_m_d') . '.csv'; 

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    #[Route('/shipping-invoice/export', name: 'report_shipping_invoice_export', methods: ['GET', 'POST'])]
    public function downloadSampleCsv(): Response
    {
        $header = [
            'Reference No.1',
            'Reference No.2',
            'Reference No.3',
            'Tracking Number',
            'Account Number',
            'Invoice Number',
            'Invoice Date',
            'Amount Due',
            'Weight',
            'Zone',
            'Service Level',
            'Invoice Section',
            'Invoice Due Date',
            'Sender Name',
            'Sender Company Name',
            'Sender Street',
            'Sender City',
            'Sender State',
            'Sender Zip Code',
            'Receiver Name',
            'Receiver Company Name',
            'Receiver Street',
            'Receiver City',
            'Receiver State',
            'Receiver Zip Code',
            'Receiver Country or Territory',
            'Pickup Record',
            'Pickup Date',
            'Third Party',
            'Billed Charge',
            'Incentive Credit'
        ];

        $filename = 'sample-shipping-invoice.csv';
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $header);
        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    #[Route('/report-order-export', name: 'report_order_export')]
    public function exportOrders(): Response
    {
        return $this->render('admin/order_export/index.html.twig', []);
    }

}
