<?php

namespace App\Controller\Cron;

use App\Constant\CogsConstant;
use App\Entity\Admin\Expenditure;
use App\Entity\Admin\ShippingInvoice;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderTransaction;
use App\Entity\Product;
use App\Entity\Reports\DailyCogsReport;
use App\Entity\Reports\MonthlyCogsReport;
use App\Entity\Reports\OrderCogsReport;
use App\Entity\Store;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\StoreConfigEnum;
use App\Helper\Order\MonthlyMaterialCostBreakdown;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

class UpdateCogsReportController extends AbstractController
{
    public function __construct(protected readonly EntityManagerInterface $entityManager, private readonly StoreInfoService $storeInfoService,  private readonly MailerInterface $mailer)
    {
    }

    #[Route(path: '/update-cogs', name: 'cron_update_cogs')]
    public function index(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $date = $date->modify('-1 day'); // normally runs on the yesterday date
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        $this->updateDailyCogsReport($date);

        $this->sendDailyCogEmail();

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }

    #[Route(path: '/update-cogs-monthly', name: 'cron_update_monthly_cogs')]
    public function updateMonthly(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        $firstDateOfMonth = $date->modify('first day of this month');
        $lastDateOfMonth = $date->modify('last day of this month');

        // Loop through each day of the month
        $currentDate = $firstDateOfMonth;
        while ($currentDate <= $lastDateOfMonth) {
            $this->updateDailyCogsReport($currentDate);
            $currentDate = $currentDate->modify('+1 day');
        }

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }

    public function updateDailyCogsReport(\DateTimeImmutable $date): void
    {
        $startOfDay = new \DateTimeImmutable($date->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($date->format('Y-m-d 23:59:59'));

        $dailyReport = $this->getDailyReport($date);
        $this->updateOrderData($dailyReport, $startOfDay, $endOfDay);


        $orderCogsReports = $this->entityManager->getRepository(OrderCogsReport::class)->findBy([
            'dailyCogsReport' => $dailyReport,
        ]);

        foreach ($orderCogsReports as $report) {
            $this->entityManager->remove($report);            
        }

        $this->entityManager->flush();

        if (!$dailyReport->isHasCustomData()) {
            $this->updateMaterialCost($dailyReport, $startOfDay, $endOfDay);
        }

        $this->updateShippingCosts($dailyReport, $startOfDay, $endOfDay);
        $this->updateWeightedAdsCost($dailyReport, $startOfDay, $endOfDay);
        $this->updateLaborCost($dailyReport, $startOfDay, $endOfDay);

        $this->entityManager->persist($dailyReport);
        $this->entityManager->flush();
    }

    private function updateWeightedAdsCost(DailyCogsReport $dailyReport, \DateTimeImmutable $startOfDay, \DateTimeImmutable $endOfDay): void
    {
        $orders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)->getResult();

        $totalSales = $dailyReport->getTotalSales();
        $totalAdsCost = $dailyReport->getTotalAdsCost();

        if ($totalSales > 0) {
            /** @var Order $order */
            foreach ($orders as $order) {
                $percentage = ($order->getTotalAmount() / $totalSales) * 100;
                $order->setWeightedAdsCost(($percentage / 100) * $totalAdsCost);
                $this->entityManager->persist($order);
            }
            $this->entityManager->flush();
        } else {
            // Optional: Log a warning or handle this case appropriately.
            // Example: $this->logger->warning('Total sales is zero, skipping weighted ad cost calculation.');
        }
        $this->entityManager->flush();
    }

    private function updateShippingCosts(DailyCogsReport $dailyReport, \DateTimeImmutable $startOfDay, \DateTimeImmutable $endOfDay): void
    {

        $orders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)->getResult();

        $shippingCost = 0;
        $shippingAdjustment = 0;
        $shippingTotal = 0;
        $totalShippingCost = 0;

        /** @var Order $order */
        foreach ($orders as $order) {
            foreach ($order->getOrderShipments() as $orderShipment) {
                $shippingCost += $order->getShippingCostByOrder(ShippingInvoice::INVOICE_TYPE_OUTBOUND, $orderShipment->getTrackingId());
                $shippingAdjustment += $order->getShippingCostByOrder(ShippingInvoice::INVOICE_TYPE_ADJUSTMENT, $orderShipment->getTrackingId());
                $shippingTotal += $order->getShippingCostByOrder(ShippingInvoice::INVOICE_TYPE_TOTAL, $orderShipment->getTrackingId());
            }
            $shippingCosts = $order->getShippingCosts();
            if (isset($shippingCosts['shippingCharges']) && $shippingCosts['shippingCharges'] > 0) {
                $totalShippingCost += $shippingCosts['shippingCharges'];
            }else{
                $totalShippingCost += $order->getShippingCost();
            }
        }

        $dailyReport->setShippingCostBreakDown([
            'shippingCharges' => $totalShippingCost,
            'shippingAdjustment' => $shippingAdjustment,
            'shippingTotal' => $totalShippingCost + $shippingAdjustment
        ]);
        $dailyReport->setTotalShippingCost($totalShippingCost + $shippingAdjustment);

    }

    private function updateMaterialCost(DailyCogsReport $dailyReport, \DateTimeImmutable $startOfDay, \DateTimeImmutable $endOfDay): void
    {

        // Fetch orders within the date range
        $orders = $this->entityManager->getRepository(Order::class)
            ->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)
            ->getResult();

        $breakdown = new MonthlyMaterialCostBreakdown($orders);

        if($breakdown->getTotalMaterialCost() == $dailyReport->getOriginalMaterialCost() || $dailyReport->getOriginalMaterialCost() == null){
            $dailyReport->setMaterialCost($breakdown->getTotalMaterialCost());
        }

        $dailyReport->setMaterialCostBreakdown([
            'precuts' => $breakdown->getPrecuts(),
            'sheetsUsed' => $breakdown->getSheetsUsed(),
            'inkCost' => $breakdown->getInkCost(),
            'sheets' => $breakdown->getSheets(),
            'totalSheetsUsed' => $breakdown->getTotalSheetsUsed(),
            'totalSignsPrinted' => $breakdown->getTotalSignsPrinted(),
            'wireStakes' => $breakdown->getWireStakeUsed(),
            'wireStakeUsed' => $breakdown->getWireStakeUsed(),
            'wireStakeCost' => $breakdown->getWireStakeCost(),
            'totalWireStakeUsed' => $breakdown->getTotalWireStakeUsed(),
            'sheetsSingleSidedPrint' => $breakdown->getSheetsSingleSidedPrint(),
            'sheetsDoubleSidedPrint' => $breakdown->getSheetsDoubleSidedPrint(),
            'totalBoxCost' => $breakdown->getTotalBoxCost(),
            'totalMaterialCost' => $breakdown->getTotalMaterialCost(),
            'materialCostBreakdown' => $breakdown->getMaterialCostBreakdown(),
            'totalLaborCost' => $breakdown->getTotalLaborCost(),
        ]);
    }

    private function updateLaborCost(DailyCogsReport $dailyReport, \DateTimeImmutable $startOfDay, \DateTimeImmutable $endOfDay): void
    {

        // Fetch orders within the date range
        $orders = $this->entityManager->getRepository(Order::class)
            ->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)
            ->getResult();

        $breakdown = new MonthlyMaterialCostBreakdown($orders);

        if($dailyReport->getLaborCost() == $dailyReport->getOriginalLaborCost() || $dailyReport->getOriginalLaborCost() == null){
            if ($dailyReport->getLaborCost() > 0) {
                $dailyReport->setLaborCost($dailyReport->getLaborCost());
            }else {
                $dailyReport->setLaborCost($breakdown->getTotalLaborCost());
            }
        }

        $this->distributeOrderLaborCost($orders, $dailyReport->getLaborCost());
    }


    private function updateOrderData(DailyCogsReport $dailyReport, \DateTimeImmutable $startOfDay, \DateTimeImmutable $endOfDay): void
    {

        $totalOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, onlyCount: true, paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::VOIDED, PaymentStatusEnum::PENDING])->getOneOrNullResult();
        $dailyReport->setTotalOrders($totalOrders['totalOrders'] ?? 0);

        $totalSales = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay,endDate: $endOfDay, onlyAmount: true)->getOneOrNullResult();
        $dailyReport->setTotalSales($totalSales['totalAmount'] ?? 0);

        $totalPaidOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, onlyReceivedAmount: true, paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED], onlyCount: true)->getOneOrNullResult();
        $dailyReport->setTotalPaidOrders($totalPaidOrders['totalOrders'] ?? 0);

        $totalPaidSales = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, onlyReceivedAmount: true, onlyAmount: true)->getOneOrNullResult();
        $dailyReport->setTotalPaidSales($totalPaidSales['totalAmount'] ?? 0);

        $totalCheckOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentMethod: PaymentMethodEnum::CHECK, onlyCount: true)->getOneOrNullResult();
        $dailyReport->setTotalCheckOrders($totalCheckOrders['totalOrders'] ?? 0);

        $totalCheckSales = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentMethod: PaymentMethodEnum::CHECK, onlyAmount: true)->getOneOrNullResult();
        $dailyReport->setTotalCheckSales($totalCheckSales['totalAmount'] ?? 0);

        $totalPaymentLink = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, onlyAmount: true, paymentLinkAmount: true)->getOneOrNullResult();
        $dailyReport->setTotalPaymentLinkAmount($totalPaymentLink['totalAmount'] ?? 0);

        $totalSDPLOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentMethod: PaymentMethodEnum::SEE_DESIGN_PAY_LATER, onlyCount: true)->getOneOrNullResult();
        $dailyReport->setTotalPayLaterOrder($totalSDPLOrders['totalOrders'] ?? 0);

        $totalSDPLSales = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentMethod: PaymentMethodEnum::SEE_DESIGN_PAY_LATER, onlyAmount: true)->getOneOrNullResult();
        $dailyReport->setTotalPayLaterSales($totalSDPLSales['totalAmount'] ?? 0);

        $totalRefundedOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentStatus: [OrderStatusEnum::PARTIALLY_REFUNDED], onlyCount: true)->getOneOrNullResult();
        $dailyReport->setTotalRefundedOrder($totalRefundedOrders['totalOrders'] ?? 0);

        $refundedOrders = $this->entityManager->getRepository(OrderTransaction::class)->filterSales(fromDate: $startOfDay, endDate: $endOfDay, onlyOrderIds: true, status: [OrderStatusEnum::PARTIALLY_REFUNDED])->getOneOrNullResult();
        $refundedOrderIds = array_filter(explode(',', $refundedOrders['orderIds'] ?? ''));
        $dailyReport->setRefundedOrders($refundedOrderIds ?? []);

        $totalRefundedAmount = $this->entityManager->getRepository(OrderTransaction::class)->filterSales(fromDate: $startOfDay, endDate: $endOfDay, onlyrefundedAmount: true, status: [OrderStatusEnum::PARTIALLY_REFUNDED])->getOneOrNullResult();
        
        if($dailyReport->getTotalRefundedAmount() == $dailyReport->getOriginalRefundedAmount() || $dailyReport->getOriginalRefundedAmount() == null){
           $dailyReport->setTotalRefundedAmount($totalRefundedAmount['refundedAmount'] ?? 0);
        }
        // $totalShippingCost = $this->entityManager->getRepository(Order::class)->getShippingCostForOrderBetween(fromDate: $startOfDay, endDate: $endOfDay);
        // $dailyReport->setTotalShippingCost($totalShippingCost['shippingCost'] ?? 0);

        $cancelledOrders = $this->entityManager->getRepository(Order::class)->filterOrder(status: [OrderStatusEnum::CANCELLED], fromDate: $startOfDay, endDate: $endOfDay, onlyOrderIds: true, canceledOrders: true)->getOneOrNullResult();
        $cancelledOrdersIds = array_filter(explode(',', $cancelledOrders['orderIds'] ?? ''));
        $dailyReport->setCancelledOrders($cancelledOrdersIds ?? []);

        $cancelledSales = $this->entityManager->getRepository(Order::class)->getCanceledOrders(status: [OrderStatusEnum::CANCELLED], fromDate: $startOfDay, endDate: $endOfDay);

        $dailyReport->setCancelledSales($cancelledSales['totalAmount'] ?? 0);

    }

    private function getDailyReport(\DateTimeImmutable $date)
    {
        $dailyReport = $this->entityManager->getRepository(DailyCogsReport::class)->findOneBy(['date' => $date]);
        if (!$dailyReport) {
            $month = $this->getMonthlyReport($date);
            $dailyReport = new DailyCogsReport();
            $dailyReport->setMonth($month);
            $dailyReport->setDate($date);
            $this->entityManager->persist($dailyReport);
            $this->entityManager->flush();
        }
        return $dailyReport;
    }

    private function getMonthlyReport(\DateTimeImmutable $dateTime): MonthlyCogsReport
    {
        $firstDate = $dateTime->modify('first day of this month');
        $month = $this->entityManager->getRepository(MonthlyCogsReport::class)->findOneBy(['date' => $firstDate]);
        if (!$month) {
            $month = new MonthlyCogsReport();
            $month->setDate($firstDate);
            $month->setStore($this->entityManager->getRepository(Store::class)->find(1));
            $this->entityManager->persist($month);
            $this->entityManager->flush();
        }
        return $month;
    }

    private function sendDailyCogEmail(): void
    {

        $currentDate = new \DateTimeImmutable();
        $firstDayOfMonth = $currentDate->modify('first day of this month');
        $month = $this->entityManager->getRepository(MonthlyCogsReport::class)->findOneBy(['date' => $firstDayOfMonth]);
        $dailyCogsReports = $this->entityManager->getRepository(DailyCogsReport::class)->findBy(['month' => $month], ['date' => 'ASC']);
        $storeName = $this->storeInfoService->getStoreName();
        $email = new TemplatedEmail();
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $email->subject('YSP COGs Report for '. $month->getDate()->format('M Y'));

        $email->to('khalilmaknojia@gmail.com', 'abilmaknojia@gmail.com', 'sharez.prasla@gmail.com', 'azimmaknojia@gmail.com');
        $email->cc('manoj@geekybones.com');
        $email->htmlTemplate('emails/admin/daily-cogs.html.twig')->context([
            'month' => $month,
            'dailyCogsReports' => $dailyCogsReports,
        ]);
        $this->mailer->send($email);
    }

    private function distributeOrderLaborCost(array $orders, float $totalLaborCost): void
    {
        $totalSheetsUsed = 0;

        foreach ($orders as $order) {
            $materialCost = $order->getMaterialCost();
            $sheetsSingleSidedPrint = $materialCost['sheetsSingleSidedPrint'] ?? 0;
            $sheetsDoubleSidedPrint = $materialCost['sheetsDoubleSidedPrint'] ?? 0;
            $totalSheetsUsed += $sheetsSingleSidedPrint + $sheetsDoubleSidedPrint;
        }

        if ($totalSheetsUsed <= 0) {
            return;
        }

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