<?php

namespace App\SlackSchema;

use App\Entity\Order;
use App\Helper\AddressHelper;

class AddressUpdatedSchema
{
    public static function get(Order $order, string $type, array $currentAddress, array $newAddress): string
    {
        $blocks = [];

        $readableType = ucwords(preg_replace('/(?<!^)([A-Z])/', ' $1', $type));

        SlackSchemaBuilder::markdown(
            $blocks,
            sprintf("*%s UPDATED*", strtoupper($readableType))
        );

        SlackSchemaBuilder::markdown(
            $blocks,
            sprintf("*Order ID:* %s", $order->getOrderId())
        );

        // Previous address
        SlackSchemaBuilder::markdown(
            $blocks,
            "\n*Previous Address:*\n" . AddressHelper::formatAddressBlock($currentAddress)
        );

        // New address
        SlackSchemaBuilder::markdown(
            $blocks,
            "\n*New Address:*\n" . AddressHelper::formatAddressBlock($newAddress)
        );

        return json_encode([
            'blocks' => $blocks,
        ]);
    }
}
