<?php

namespace App\SlackSchema;

use App\Entity\Order;
use App\Entity\ProductType;
use App\Enum\OrderChannelEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Helper\ShippingChartHelper;
use App\Service\HostBasedRouterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NewOrderSchema
{

    public static function get(Order $order, $options, EntityManagerInterface $entityManager, ?ParameterBagInterface $params = null): bool|string
    {
        $blocks = [];
        $customerShipping = $order->getShippingMetaDataKey('customerShipping') ?? [];
        $productType = $entityManager->getRepository(ProductType::class)->findBySlug(slug: 'yard-sign');
        $shipping = $productType->getShipping();
        $shippingBuilder = new ShippingChartHelper();
        $fullShippingChart = $shippingBuilder->build($shipping);
        $quantity = $order->getTotalQuantity();
        $shippingChart = $shippingBuilder->getShippingByQuantity($quantity, $fullShippingChart);

        $tag = $shippingBuilder->buildTag($shippingChart, $customerShipping);

        $secondTag = $order->isNeedProof() == false ? '(PROOF PRE-APPROVED)' : '';
        SlackSchemaBuilder::markdown($blocks, "*NEW ORDER RECEIVED* {$tag} {$secondTag}\n\n");
        SlackSchemaBuilder::markdown($blocks, "*Order ID:* " . $order->getOrderId());

        $shippingAddress = $order->getShippingAddress();
        $shippingAddressText = $shippingAddress['firstName'] . ' ' . $shippingAddress['lastName'];

        SlackSchemaBuilder::markdown($blocks, "*Shipping Name:* " . $shippingAddressText);
        if ($order->isIsManual()) {
            $channelConfig = ['label' => $order->getOrderChannel()->label()];
            SlackSchemaBuilder::markdown($blocks, "*Order Type:* " . $channelConfig['label']);
        }
        SlackSchemaBuilder::markdown($blocks, self::createOrderItems($order, $options['showPrice'] ?? true));

        if (isset($options['totalSummary']) && $options['totalSummary']) {
            SlackSchemaBuilder::markdown($blocks, self::createTotalSummary($order));
        }

        if (isset($options['viewOrderLink']) && isset($options['proofsLink']) && $options['viewOrderLink'] && $options['proofsLink']) {
            SlackSchemaBuilder::button($blocks, [
                [
                    'label' => 'View Order',
                    'link' => HostBasedRouterService::replaceWithAdminHost($options['viewOrderLink'], $params),
                ],
                [
                    'label' => 'Proofs',
                    'link' => HostBasedRouterService::replaceWithAdminHost($options['proofsLink'], $params),
                ],
            ]);
        }

        return json_encode([
            'blocks' => $blocks,
        ]);
    }

    public static function createOrderItems(Order $order, bool $showPrice = true): string
    {
        $orderItems = $order->getOrderItems();
        $totalItems = count($orderItems);
        $limit = 15;
        $orderItemsContent = "*Order Items*\n---------------------------------------------------";
        $counter = 1;

        foreach ($orderItems as $index => $item) {
            if ($index >= $limit) {
                $orderItemsContent .= "\n+ " . ($totalItems - $limit) . " more items, please review in admin.";
                break;
            }

            $template = $item->getProduct();
            $product = $template->getParent();
            if ($item->getMetaDataKey('isCustomSize')) {
                $name = $item->getMetaDataKey('customSize')['templateSize']['width'] . 'x' . $item->getMetaDataKey('customSize')['templateSize']['height'];
            } else if ($item->getMetaDataKey('isWireStake')) {
                $name = $template->getLabel() ?? $template->getName();
            } else {
                $name = $template->getName();
            }

            $itemName = $counter . '. ' . $name . ' (' . $product->getSku() . ')';
            if (!$showPrice) {
                $orderItemsContent .= "\n" . $itemName . ' - Qty ' . $item->getQuantity();
            } else {
                $orderItemsContent .= "\n" . $itemName . ' - ' . $item->getQuantity() . ' x $' . number_format($item->getUnitAmount(), 2) . ' = $' . number_format($item->getTotalAmount(), 2);
            }
            $counter++;
        }
        $orderItemsContent .= "\n---------------------------------------------------";
        return $orderItemsContent;
    }

    public static function createTotalSummary(Order $order): string
    {
        $totalAmountContent = "*Delivery Date:* " . $order->getDeliveryDate()->format('M d, Y');
        $totalAmountContent .= "\n*Delivery Cost:* $" . number_format($order->getShippingAmount(), 2);
        $totalAmountContent .= "\n*Total Units:* " . $order->getOrderItems()->count();
        $totalAmountContent .= "\n*Total Amount:* $" . number_format($order->getTotalAmount(), 2);
        $totalAmountContent .= "\n*Payment Status:* " . PaymentStatusEnum::getLabel($order->getPaymentStatus());
        $totalAmountContent .= "\n*Payment Method:* " . PaymentMethodEnum::getLabel($order->getPaymentMethod());
        return $totalAmountContent;
    }
}