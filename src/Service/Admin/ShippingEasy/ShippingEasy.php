<?php

namespace App\Service\Admin\ShippingEasy;


use App\Entity\Order;
use App\Entity\OrderShipment;
use App\Enum\OrderStatusEnum;
use App\Enum\ShippingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ShippingEasy extends Base
{
    public function __construct(ParameterBagInterface $parameterBag, Signature $signature, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct($parameterBag, $signature);
    }

    public function createOrder(Order $order): array
    {
        $endpoint = '/api/stores/' . $this->storeKey . '/orders';
        $body = $this->generateCreateOrderBody($order);
        $response = $this->request($endpoint, $body);
        if ($response['success']) {
            $data = $response['data']['order'];
            $order->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
            if (isset($data['order_status'])) {
                $order->setShippingStatus($data['order_status']);
            }
            $order->setShippingMethod(ShippingEnum::SHIPPING_EASY);
            $order->setShippingOrderId($data['id']);
            $order->setShippingMetaData($data);

            $orderShipment = new OrderShipment();
            $orderShipment->setOrder($order);
            $orderShipment->setShipmentId($data['id']);
            $orderShipment->setStatus($data['order_status']);
            $orderShipment->setParcelId($data['id']);
            $orderShipment->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($orderShipment);
            $this->entityManager->flush();

            $this->entityManager->persist($order);
            $this->entityManager->flush();
        }

        return $response;
    }

    public function getOrders(string $status = 'ready_for_shipment'): ?array
    {
        $body = [];
        if ($status) {
            $body['status'] = $status;
        }
        $endpoint = '/api/orders';
        return $this->request($endpoint, $body, 'GET');
    }

    public function getOrder(string $seOrderId): ?array
    {
        $endpoint = '/api/orders/' . $seOrderId;
        return $this->request($endpoint, null, 'GET');
    }

    private function generateCreateOrderBody(Order $order): array
    {
        // 1 LB = 16 Ounce
        $singleSignWeightInLb = 0.1 * 16;
        $singleWireStakeWeight = 0.1 * 16;
        $items = [];
        foreach ($order->getOrderItems() as $item) {
            $itemWeight = $singleSignWeightInLb * $item->getQuantity();
            $frame = $item->getAddOns()['frame'] ?? [];
            if (isset($frame['key']) && $frame['key'] !== 'NONE') {
                $itemWeight += $singleWireStakeWeight * $item->getQuantity();
            }
            $template = $item->getProduct();
            $product = $template->getParent();
            $itemName = $template->getName();

            $customSizeMeta = $item->getMetaDataKey('customSize');
            if ($item->getMetaDataKey('isCustomSize') && !empty($customSizeMeta)) {
                $itemName = $customSizeMeta['templateSize']['width'] . 'x' . $customSizeMeta['templateSize']['height'];
            }

            $items[] = [
                "item_name" => $itemName,
                "sku" => $product->getSku(),
                "unit_price" => $item->getUnitAmount(),
                "total_excluding_tax" => $item->getUnitAmount(),
                "price_excluding_tax" => "",
                "weight_in_ounces" => $itemWeight,
                "quantity" => $item->getQuantity()
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        return [
            'external_order_identifier' => ($this->env === 'test' ? 'test-' . time() . '-' : '') . $order->getOrderId(),
            'ordered_at' => $order->getOrderAt()->format('Y-m-d H:i:s O'),
            'notes' => "",
            "internal_notes" => "",
            "discount_amount" => $order->getAdminDiscountAmount(),
            "coupon_discount" => $order->getCouponDiscountAmount(),
            "subtotal_including_tax" => $order->getSubTotalAmount(),
            "subtotal_excluding_tax" => $order->getSubTotalAmount(),
            "subtotal_tax" => $order->getSubTotalAmount(),
            "total_tax" => "0.00",
            "total_including_tax" => $order->getTotalAmount(),
            "base_shipping_cost" => $order->getShippingAmount(),
            "shipping_cost_including_tax" => $order->getShippingAmount(),
            "shipping_cost_excluding_tax" => "",
            "shipping_cost_tax" => $order->getShippingAmount(),
            "recipients" => [
                [
                    "first_name" => $shippingAddress['firstName'],
                    "last_name" => $shippingAddress['lastName'],
                    "company" => '',
                    "email" => $shippingAddress['email'],
                    "phone_number" => $shippingAddress['phone'],
                    "residential" => "true",
                    "address" => $shippingAddress['addressLine1'],
                    "address2" => $shippingAddress['addressLine2'],
                    "province" => "",
                    "state" => $shippingAddress['state'],
                    "city" => $shippingAddress['city'],
                    "country" => $shippingAddress['country'],
                    "postal_code" => $shippingAddress['zipcode'],
                    "postal_code_plus_4" => "",
                    "shipping_method" => "ups_wallet",
                    'shipping_zone_id' => '',
                    "items_total" => count($items),
                    "line_items" => $items
                ]
            ]
        ];
    }
}