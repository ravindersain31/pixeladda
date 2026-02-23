<?php


namespace App\SlackSchema;

use App\Entity\Order;
use App\Service\HostBasedRouterService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderApprovedSchema
{

    public static function get(Order $order, UrlGeneratorInterface $urlGenerator, ?array $options = [], ?ParameterBagInterface $params = null): bool|string
    {
        $blocks = [];

        $tag = $order->isNeedProof() == false ? '(PROOF PRE-APPROVED)' : '';
        SlackSchemaBuilder::markdown($blocks, "*ORDER APPROVED* {$tag}\n\n");
        SlackSchemaBuilder::markdown($blocks, "*Order ID:* " . $order->getOrderId());

        $billingAddress = $order->getBillingAddress();
        $billingAddressText = $billingAddress['firstName'] . ' ' . $billingAddress['lastName'];
        SlackSchemaBuilder::markdown($blocks, "*Billing Name:* " . $billingAddressText);

        SlackSchemaBuilder::markdown($blocks, NewOrderSchema::createOrderItems($order, false));
            $url = $urlGenerator->generate('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $buttons = [
            ['label' => 'Customer Proof Link', 'link' => $urlGenerator->generate('order_proof', ['oid' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => 'View Order', 'link' => HostBasedRouterService::replaceWithAdminHost($url, $params)],
            ['label' => 'Drive Link', 'link' => $order->getDriveLink()],
        ];

        $buttons = SlackSchemaBuilder::createButtons($buttons);
        SlackSchemaBuilder::button($blocks, $buttons);

        return json_encode([
            'blocks' => $blocks,
        ]);
    }
}