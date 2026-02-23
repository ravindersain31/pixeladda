<?php


namespace App\SlackSchema;


use App\Entity\Order;
use App\Enum\OrderChannelEnum;
use App\Service\HostBasedRouterService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RequestedChangesSchema
{
    public static function get(Order $order, $message, $options, ?ParameterBagInterface $params = null): bool|string
    {
        $blocks = [];

        SlackSchemaBuilder::markdown($blocks, "*CUSTOMER REQUESTED CHANGES* \n *Order ID:* " . $order->getOrderId());

        $billingAddress = $order->getBillingAddress();
        $billingAddressText = $billingAddress['firstName'] . ' ' . $billingAddress['lastName'];
        SlackSchemaBuilder::markdown($blocks, "*Billing Name:* " . $billingAddressText);
        if ($order->isIsManual()) {
            $channel = $order->getOrderChannel()->label() ?? $order->getOrderChannel();
            SlackSchemaBuilder::markdown($blocks, "*Order Type:* " . $channel);
        }
        if($order->getParent()) {
            SlackSchemaBuilder::markdown($blocks, "*Parent Order ID:* " . $order->getParent()->getOrderId());
        }
        $message = preg_replace('#<br\s*/?>#i', "\n", $message);
        $message = htmlspecialchars($message);
        SlackSchemaBuilder::markdown($blocks, "*Customer Message:*\n " . $message);


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


        return json_encode([
            'blocks' => $blocks,
        ]);
    }
}