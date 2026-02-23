<?php


namespace App\SlackSchema;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\PaymentMethodEnum;
use App\Service\HostBasedRouterService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentLinkPaidSchema
{

    public static function get(Order $order, OrderTransaction $transaction, $options, ?ParameterBagInterface $params = null): bool|string
    {
        $blocks = [];

        SlackSchemaBuilder::markdown($blocks, "*PAYMENT LINK RECEIVED* \n\n *Order ID:* " . $order->getOrderId());

        $billingAddress = $order->getBillingAddress();
        $billingAddressText = $billingAddress['firstName'] . ' ' . $billingAddress['lastName'];
        SlackSchemaBuilder::markdown($blocks, "*Billing Name:* " . $billingAddressText);
        SlackSchemaBuilder::markdown($blocks, "*Payment Link Id:* " . $transaction->getTransactionId());
        SlackSchemaBuilder::markdown($blocks, "*Amount:* $" . number_format($transaction->getAmount(), 2));
        SlackSchemaBuilder::markdown($blocks, "*Payment Method:* " . PaymentMethodEnum::getLabel($transaction->getPaymentMethod()));
        SlackSchemaBuilder::markdown($blocks, "*Comments:* " . $transaction->getComment());

        SlackSchemaBuilder::button($blocks, [
            [
                'label' => 'Payment Link',
                'link' => $options['paymentLink'],
            ],
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