<?php


namespace App\SlackSchema;

use App\Entity\AdminUser;
use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Enum\OrderChannelEnum;

class NewProofUploadedSchema
{

    public static function get(Order $order, OrderMessage $proof, $options): bool|string
    {
        $blocks = [];

        /**
         * @var AdminUser $uploadedBy
         */
        $uploadedBy = $proof->getSentBy();

        SlackSchemaBuilder::markdown($blocks, "*NEW PROOF UPLOADED* \n\n *Order ID:* " . $order->getOrderId());

        $billingAddress = $order->getBillingAddress();
        $billingAddressText = $billingAddress['firstName'] . ' ' . $billingAddress['lastName'];
        SlackSchemaBuilder::markdown($blocks, "*Billing Name:* " . $billingAddressText);
        if ($order->isIsManual()) {
            $channel = $order->getOrderChannel()->label() ?? $order->getOrderChannel();
            SlackSchemaBuilder::markdown($blocks, "*Order Type:* " . $channel);
        }
        if ($order->getParent()) {
            SlackSchemaBuilder::markdown($blocks, "*Parent Order ID:* " . $order->getParent()->getOrderId());
        }
        SlackSchemaBuilder::markdown($blocks, "*Uploaded By:* " . $uploadedBy->getName());
        if ($proof->getContent()) {
            SlackSchemaBuilder::markdown($blocks, "*Comments by Designer:*\n " . strip_tags($proof->getContent()));
        }

        SlackSchemaBuilder::button($blocks, [
            [
                'label' => 'Customer Proof Link',
                'link' => $options['customerProofLink'],
            ],
            [
                'label' => 'View Order',
                'link' => $options['viewOrderLink'],
            ],
            [
                'label' => 'Proofs',
                'link' => $options['proofsLink'],
            ],
        ]);


        return json_encode([
            'blocks' => $blocks,
        ]);
    }
}