<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\OrderItem;

#[Route('/api/orders')]
class OrderExportController extends AbstractController
{

    public function __construct(private readonly OrderRepository $orderRepository) {}

    /**
     * Get paginated orders for export
     * Returns JSON data that frontend will convert to CSV
     */
    #[Route('/export-data', name: 'api_orders_export_data', methods: ['GET'])]
    public function getOrdersData(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $limit = min((int)$request->query->get('limit', 100), 500); // Max 500 per request
        $offset = (int)$request->query->get('offset', 0);

        if (!$startDate || !$endDate) {
            return $this->json([
                'success' => false,
                'error' => 'start_date and end_date are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59);

            $totalCount = $this->orderRepository->countOrdersByDateRange($start, $end);

            $orders = $this->orderRepository->findOrdersByDateRange(
                $start,
                $end,
                $limit,
                $offset
            );

            $ordersData = [];
            foreach ($orders as $order) {
                $orderData = $this->transformOrder($order);
                $ordersData[] = $orderData;
            }

            return $this->json([
                'success' => true,
                'data' => $ordersData,
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'current_count' => count($ordersData),
                    'has_more' => ($offset + $limit) < $totalCount
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get export metadata (total count, date range, etc.)
     */
    #[Route('/export-meta', name: 'api_orders_export_meta', methods: ['GET'])]
    public function getExportMeta(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        if (!$startDate || !$endDate) {
            return $this->json([
                'success' => false,
                'error' => 'start_date and end_date are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59);

            $totalCount = $this->orderRepository->countOrdersByDateRange($start, $end);

            return $this->json([
                'success' => true,
                'meta' => [
                    'total_orders' => $totalCount,
                    'start_date' => $start->format('Y-m-d H:i:s'),
                    'end_date' => $end->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function transformOrder($order): array
    {
        $billingAddress = $order->getBillingAddress();
        $storeShortName = $order->getStore() ? $order->getStore()->getShortName() : 'YSP';
        $couponCode = $order->getCoupon() ? $order->getCoupon()->getCode() : '';
        $totalDiscount = (float)$order->getCouponDiscountAmount() + (float)$order->getAdminDiscountAmount();

        $orderItems = $order->getOrderItemsAll();

        if ($orderItems->isEmpty()) {
            return [
                'order_id' => $order->getOrderId(),
                'order_date' => $order->getOrderAt()->format('Y-m-d H:i:s'),
                'store_name' => $storeShortName,
                'customer_first_name' => $billingAddress['firstName'] ?? '',
                'customer_last_name' => $billingAddress['lastName'] ?? '',
                'customer_email' => $billingAddress['email'] ?? '',
                'customer_phone' => $billingAddress['phone'] ?? '',
                'address_line_1' => $billingAddress['addressLine1'] ?? '',
                'address_line_2' => $billingAddress['addressLine2'] ?? '',
                'city' => $billingAddress['city'] ?? '',
                'state' => $billingAddress['state'] ?? '',
                'zipcode' => $billingAddress['zipcode'] ?? '',
                'country' => $billingAddress['country'] ?? '',
                'item_name' => '',
                'item_type' => '',
                'item_sku' => '',
                'category' => '',
                'productType' => '',
                'item_quantity' => 0,
                'item_unit_price' => 0,
                'item_addons_amount' => 0,
                'item_shipping_amount' => 0,
                'item_total_amount' => 0,
                'order_sub_total' => (float)$order->getSubTotalAmount(),
                'order_shipping_amount' => (float)$order->getShippingAmount(),
                'order_discount' => $totalDiscount,
                'order_total_amount' => (float)$order->getTotalAmount(),
                'payment_method' => $order->getPaymentMethod(),
                'payment_status' => $order->getPaymentStatus(),
                'order_status' => $order->getStatus(),
                'is_super_rush' => $order->isIsSuperRush() ? 'Yes' : 'No',
                'coupon_code' => $couponCode,
            ];
        }

        $rows = [];
        /** @var OrderItem $item */
        foreach ($orderItems as $item) {
            $itemName = '';
            $itemSku = '';
            $category = '';
            $productType = '';
            if ($item->getProduct()) {
                $customSize = $item->getMetaDataKey('customSize');
                $isWireStake = $item->getMetaDataKey('isWireStake');
                if (is_array($customSize) && !$isWireStake) {
                    $itemName = $customSize['templateSize']['width'] . 'x' . $customSize['templateSize']['height'];
                    $category = isset($customSize['category']) ? $customSize['category'] : $item->getProduct()->getParent()->getPrimaryCategory()->getSlug();
                } else {
                    $itemName = $item->getProduct()->getName();
                    $category = $item->getProduct()->getParent()->getPrimaryCategory()->getSlug();
                }
                $itemSku = $item->getProduct()->getParent()->getSku();
                $productType = $item->getProduct()->getParent()->getProductType()->getSlug();
            } elseif ($item->getItemName()) {
                $itemName = $item->getItemName();
            }

            $rows[] = [
                'order_id' => $order->getOrderId(),
                'order_date' => $order->getOrderAt()->format('Y-m-d H:i:s'),
                'store_name' => $storeShortName,
                'customer_first_name' => $billingAddress['firstName'] ?? '',
                'customer_last_name' => $billingAddress['lastName'] ?? '',
                'customer_email' => $billingAddress['email'] ?? '',
                'customer_phone' => $billingAddress['phone'] ?? '',
                'address_line_1' => $billingAddress['addressLine1'] ?? '',
                'address_line_2' => $billingAddress['addressLine2'] ?? '',
                'city' => $billingAddress['city'] ?? '',
                'state' => $billingAddress['state'] ?? '',
                'zipcode' => $billingAddress['zipcode'] ?? '',
                'country' => $billingAddress['country'] ?? '',
                'item_name' => $itemName,
                'item_type' => $item->getItemType(),
                'item_sku' => $itemSku,
                'category' => $category,
                'productType' => $productType,
                'item_quantity' => $item->getQuantity(),
                'item_unit_price' => (float)$item->getPrice(),
                'item_addons_amount' => (float)$item->getAddOnsAmount(),
                'item_shipping_amount' => (float)$item->getShippingAmount(),
                'item_total_amount' => (float)$item->getTotalAmount(),
                'order_sub_total' => (float)$order->getSubTotalAmount(),
                'order_shipping_amount' => (float)$order->getShippingAmount(),
                'order_discount' => $totalDiscount,
                'order_total_amount' => (float)$order->getTotalAmount(),
                'payment_method' => $order->getPaymentMethod(),
                'payment_status' => $order->getPaymentStatus(),
                'order_status' => $order->getStatus(),
                'is_super_rush' => $order->isIsSuperRush() ? 'Yes' : 'No',
                'coupon_code' => $couponCode,
            ];
        }

        return $rows;
    }
}
