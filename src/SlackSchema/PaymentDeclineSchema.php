<?php

namespace App\SlackSchema;

use App\Entity\Order;
use App\Service\HostBasedRouterService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentDeclineSchema
{
    public static function get(Order $order, $message, $options, ?ParameterBagInterface $params = null): bool|string
    {
        $blocks = [];

        SlackSchemaBuilder::markdown($blocks, "*ORDER PAYMENT DECLINED* \n\n *Order ID:* " . $order->getOrderId());
        SlackSchemaBuilder::markdown($blocks, "*Error Message:* " . $message);

        $shippingAddress = $order->getShippingAddress();
        $shippingAddressText = $shippingAddress['firstName'] . ' ' . $shippingAddress['lastName'];

        SlackSchemaBuilder::markdown($blocks, "*Shipping Name:* " . $shippingAddressText);
        SlackSchemaBuilder::markdown($blocks, NewOrderSchema::createOrderItems($order));
        SlackSchemaBuilder::markdown($blocks, NewOrderSchema::createTotalSummary($order));

        SlackSchemaBuilder::button($blocks, [
            [
                'label' => 'View Order',
                'link' => HostBasedRouterService::replaceWithAdminHost($options['viewOrderLink'], $params),
            ],
        ]);

        return json_encode([
            'blocks' => $blocks,
        ]);
    }

}