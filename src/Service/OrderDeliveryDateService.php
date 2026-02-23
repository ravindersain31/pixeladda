<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\ProductType;
use App\Enum\OrderStatusEnum;
use App\Helper\ShippingChartHelper;
use Doctrine\ORM\EntityManagerInterface;

class OrderDeliveryDateService
{
    private ProductType $productType;

    public function __construct(
        private readonly ShippingChartHelper $shippingChartHelper,
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->productType = $entityManager->getRepository(ProductType::class)->findOneBy(['slug' => 'yard-sign']);
    }

    public function sync(Order $order): void
    {
        if (!$this->isOrderStatusProcessable($order)) {
            return;
        }

        $customerShipping = $order->getShippingMetaDataKey('customerShipping') ?? [];
        $dayNumber = $customerShipping['day'] ?? null;
        $isSaturdayDelivery = $customerShipping['isSaturday'] ?? false;

        $currentTime = $this->adjustCurrentTimeForShipping($isSaturdayDelivery);
        $shippingChart = $this->buildShippingChart($order, $currentTime);
        $shipping = $this->getAdjustedShipping($shippingChart, $dayNumber, $isSaturdayDelivery);

        $deliverDate = new \DateTimeImmutable($shipping['date']);
        $orderShippingDate = $order->getDeliveryDate();

        if ($orderShippingDate->getTimestamp() < $deliverDate->getTimestamp()) {
            $order->setDeliveryDate($deliverDate);
            $order->setShippingMetaDataKey('customerShipping', [
                ...$customerShipping,
                'day' => $shipping['day'],
                'date' => $shipping['date'],
            ]);

            $this->entityManager->persist($order);
            $this->entityManager->flush();
        }
    }

    public function applyFreeShipping(Order $order): void
    {
        if (
            in_array($order->getStatus(), [
                OrderStatusEnum::SHIPPED,
                OrderStatusEnum::CANCELLED,
                OrderStatusEnum::READY_FOR_SHIPMENT
            ])
        ) {
            return;
        }

        $shippingChart = $this->buildShippingChart($order, new \DateTime(), freeShipping: true);
        $customerShipping = $order->getShippingMetaDataKey('customerShipping') ?? [];

        $shipping = end($shippingChart);
        $shipping = prev($shippingChart);

        $deliverDate = new \DateTimeImmutable($shipping['date']);
        $order->setDeliveryDate($deliverDate);

        $order->setShippingMetaDataKey('customerShipping', [
            ...$customerShipping,
            'day' => $shipping['day'],
            'date' => $shipping['date'],
            'amount' => 0,
            'discount' => 0,
            'discountAmount' => 0
        ]);

        $order->removeAdditionalDiscountKey('shippingDiscount');
        $order->setTotalAmount($order->getTotalAmount() - $order->getShippingAmount());
        $order->setShippingAmount(0);

        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function getNewShippingDate(Order $order): ?\DateTimeImmutable
    {
        if (!$this->isOrderStatusProcessable($order)) {
            return null;
        }

        $customerShipping = $order->getShippingMetaDataKey('customerShipping') ?? [];
        $dayNumber = $customerShipping['day'] ?? null;
        $isSaturdayDelivery = $customerShipping['isSaturday'] ?? false;

        $currentTime = $this->adjustCurrentTimeForShipping($isSaturdayDelivery);
        $shippingChart = $this->buildShippingChart($order, $currentTime);
        $shipping = $this->getAdjustedShipping($shippingChart, $dayNumber, $isSaturdayDelivery);

        return new \DateTimeImmutable($shipping['date']);
    }

    // ====================
    // Private Helper Methods
    // ====================

    private function isOrderStatusProcessable(Order $order): bool
    {
        return in_array($order->getStatus(), [
            OrderStatusEnum::RECEIVED,
            OrderStatusEnum::DESIGNER_ASSIGNED,
            OrderStatusEnum::PROOF_UPLOADED,
            OrderStatusEnum::CHANGES_REQUESTED,
            OrderStatusEnum::PROCESSING
        ]);
    }

    private function adjustCurrentTimeForShipping(bool $isSaturdayDelivery): \DateTime
    {
        $currentTime = new \DateTime();
        $dayOfWeek = $currentTime->format('N');
        $currentHour = $currentTime->format('H:i:s');

        $isFriday = $dayOfWeek === '5';
        $isSaturday = $dayOfWeek === '6';
        $isPastCutoffFriday = $currentHour >= $this->shippingChartHelper->getCutOffHour();
        $isBeforeCutoffSaturday = $currentHour < $this->shippingChartHelper->getCutOffHour();

        if ($isFriday && $isPastCutoffFriday && $isSaturdayDelivery) {
            $this->shippingChartHelper->setCutOffHour('24:00:00');
        } elseif ($isSaturday && $isBeforeCutoffSaturday && $isSaturdayDelivery) {
            $currentTime->modify('-1 day');
        }

        return $currentTime;
    }

    private function buildShippingChart(Order $order, \DateTime $currentTime, bool $freeShipping = false): array
    {
        $this->shippingChartHelper->setEnableSorting(false);
        $this->shippingChartHelper->setFreeShippingEnabled($freeShipping);
        $this->shippingChartHelper->setSaturdayDelivery(true);

        $chart = $this->shippingChartHelper->build($this->productType->getShipping(), $currentTime);
        return $this->shippingChartHelper->getShippingByQuantity($order->getTotalQuantity(), $chart);
    }

    private function getAdjustedShipping(array $shippingChart, ?int $dayNumber, bool $isSaturdayDelivery): array
    {
        $shipping = end($shippingChart);
        $shipping = prev($shippingChart);

        if ($dayNumber) {
            $shipping = $shippingChart['day_' . $dayNumber] ?? $shipping;
        }

        $currentTime = new \DateTime();
        $dayOfWeek = $currentTime->format('N');
        $currentHour = $currentTime->format('H:i:s');
        $isFriday = $dayOfWeek === '5';
        $isSaturday = $dayOfWeek === '6';
        $isBeforeCutoffSaturday = $currentHour < $this->shippingChartHelper->getCutOffHour();
        $isPastCutoffFriday = $currentHour >= $this->shippingChartHelper->getCutOffHour();

        if (
            ($isSaturday && $isBeforeCutoffSaturday && $isSaturdayDelivery) ||
            ($isSaturdayDelivery && reset($shippingChart)['day'] == $dayNumber) ||
            ($isFriday && $isPastCutoffFriday && $isSaturdayDelivery)
        ) {
            reset($shippingChart);
            $shipping = next($shippingChart);
        }

        return $shipping;
    }
}
