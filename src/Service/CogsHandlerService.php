<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Reports\DailyCogsReport;
use App\Entity\Reports\MonthlyCogsReport;
use App\Entity\Store;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CogsHandlerService
{
    private array $store;

    public function __construct(RequestStack $requestStack, private readonly EntityManagerInterface $entityManager,)
    {
        $request = $requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $this->store = $request->get('store') ?? [];
        }
    }

    public function saveGoogleAdsSpent(\DateTimeImmutable $date, Store|string|int $store, array $data): void
    {
        $dailyCog = $this->getDailyCog($date, $store);
        $dailyCog->setGoogleAdsSpent($data['Spend']);
        $dailyCog->setGoogleAdsData($data);
        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function saveBingAdsSpent(\DateTimeImmutable $date, Store|string|int $store, array $data): void
    {
        $dailyCog = $this->getDailyCog($date, $store);
        $dailyCog->setBingAdsSpent($data['Spend']);
        $dailyCog->setBingAdsData($data);
        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function saveFacebookAdsSpent(\DateTimeImmutable $date, Store|string|int $store, array $data): void
    {
        $dailyCog = $this->getDailyCog($date, $store);
        $dailyCog->setFacebookAdsSpent($data['Spend']);
        $dailyCog->setFacebookAdsData($data);
        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function syncOrderSales(?Store $store, \DateTimeImmutable $date): void
    {
        if (!$store) {
            return;
        }

        $dailyCog = $this->getDailyCog($date, $store);
        $startOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 23:59:59'));

        $totalPaidOrders = $this->entityManager->getRepository(Order::class)->filterOrder(
            fromDate: $startOfDay,
            endDate: $endOfDay,
            paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
            onlyCount: true
        )->getOneOrNullResult();
        $dailyCog->setTotalPaidOrders($totalPaidOrders['totalOrders'] ?? 0);

        $totalPaidSales = $this->entityManager->getRepository(Order::class)->filterOrder(
            fromDate: $startOfDay,
            endDate: $endOfDay,
            paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED],
            onlyAmount: true
        )->getOneOrNullResult();
        $dailyCog->setTotalPaidSales($totalPaidSales['totalAmount'] ?? 0);

        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function syncPaymentLinkAmount(?Store $store, \DateTimeImmutable $date): void
    {
        if (!$store) {
            return;
        }

        $dailyCog = $this->getDailyCog($date, $store);
        $startOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 23:59:59'));

        $totalPaymentLink = $this->entityManager->getRepository(Order::class)->filterOrder(
            fromDate: $startOfDay,
            endDate: $endOfDay,
            paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PENDING, PaymentStatusEnum::PARTIALLY_REFUNDED],
            onlyAmount: true,
            paymentLinkAmount: true
        )->getOneOrNullResult();
        $dailyCog->setTotalPaymentLinkAmount($totalPaymentLink['totalAmount'] ?? 0);

        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function syncCancelledOrders(?Store $store, \DateTimeImmutable $date): void
    {
        if (!$store) {
            return;
        }
        $dailyCog = $this->getDailyCog($date, $store);
        $startOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 23:59:59'));

        $totalOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, onlyCount: true)->getOneOrNullResult();
        $dailyCog->setTotalOrders($totalOrders['totalOrders'] ?? 0);

        $totalSales = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, onlyAmount: true)->getOneOrNullResult();
        $dailyCog->setTotalSales($totalSales['totalAmount'] ?? 0);

        $totalPaidOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED], onlyCount: true)->getOneOrNullResult();
        $dailyCog->setTotalPaidOrders($totalPaidOrders['totalOrders'] ?? 0);

        $totalPaidSales = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentStatus: [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PARTIALLY_REFUNDED], onlyAmount: true)->getOneOrNullResult();
        $dailyCog->setTotalPaidSales($totalPaidSales['totalAmount'] ?? 0);

        $cancelledOrders = $this->entityManager->getRepository(Order::class)->filterOrder(status: [OrderStatusEnum::CANCELLED], fromDate: $startOfDay, endDate: $endOfDay, onlyOrderIds: true, canceledOrders: true)->getOneOrNullResult();
        $cancelledOrdersIds = array_filter(explode(',', $cancelledOrders['orderIds'] ?? ''));
        $dailyCog->setCancelledOrders($cancelledOrdersIds);

        $cancelledSales = $this->entityManager->getRepository(Order::class)->filterOrder(status: [OrderStatusEnum::CANCELLED], fromDate: $startOfDay, endDate: $endOfDay, onlyAmount: true, canceledOrders: true)->getOneOrNullResult();
        $dailyCog->setCancelledSales($cancelledSales['totalAmount'] ?? 0);

        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function syncRefundedAmount(?Store $store, \DateTimeImmutable $date): void
    {
        if (!$store) {
            return;
        }

        $dailyCog = $this->getDailyCog($date, $store);
        $startOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 23:59:59'));

        $refundedOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentStatus: [OrderStatusEnum::PARTIALLY_REFUNDED], onlyOrderIds: true)->getOneOrNullResult();
        $refundedOrderIds = array_filter(explode(',', $refundedOrders['orderIds'] ?? ''));
        $dailyCog->setRefundedOrders($refundedOrderIds);

        $totalRefundedOrders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay, paymentStatus: [OrderStatusEnum::PARTIALLY_REFUNDED], onlyCount: true)->getOneOrNullResult();
        $dailyCog->setTotalRefundedOrder($totalRefundedOrders['totalOrders'] ?? 0);

        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function syncShippingCost(?Store $store, \DateTimeImmutable $date): void
    {
        if (!$store) {
            return;
        }
        $dailyCog = $this->getDailyCog($date, $store);
        $startOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 00:00:00'));
        $endOfDay = new \DateTimeImmutable($dailyCog->getDate()->format('Y-m-d 23:59:59'));

        $totalShippingCost = $this->entityManager->getRepository(Order::class)->getShippingCostForOrderBetween(fromDate: $startOfDay, endDate: $endOfDay);
        $dailyCog->setTotalShippingCost($totalShippingCost['shippingCost'] ?? 0);

        $dailyCog->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
    }

    public function getDailyCog(\DateTimeImmutable $date, $store): DailyCogsReport
    {
        $startDateOfMonth = new \DateTimeImmutable($date->format('Y-m-01'));
        $monthlyCog = $this->entityManager->getRepository(MonthlyCogsReport::class)->findOneBy(['date' => $startDateOfMonth, 'store' => $store]);
        if (!$monthlyCog) {
            $monthlyCog = new MonthlyCogsReport();
            $monthlyCog->setDate($startDateOfMonth);
            if ($store instanceof Store) {
                $monthlyCog->setStore($store);
            } else {
                $monthlyCog->setStore($this->entityManager->getReference(Store::class, $store));
            }
            $this->entityManager->persist($monthlyCog);
            $this->entityManager->flush();
        }
        $dailyCog = $this->entityManager->getRepository(DailyCogsReport::class)->findOneBy(['month' => $monthlyCog, 'date' => $date]);
        if (!$dailyCog) {
            $dailyCog = new DailyCogsReport();
            $dailyCog->setDate($date);
            $dailyCog->setMonth($monthlyCog);
        }

        $this->entityManager->persist($dailyCog);
        $this->entityManager->flush();
        return $dailyCog;
    }
}