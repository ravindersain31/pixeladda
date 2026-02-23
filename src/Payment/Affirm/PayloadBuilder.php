<?php

namespace App\Payment\Affirm;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Order;
use App\Entity\Cart;
use App\Entity\OrderTransaction;

class PayloadBuilder extends Base
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(ParameterBagInterface $params, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($params);
        $this->urlGenerator = $urlGenerator;
    }

    public function buildPayload(?Order $order = null, ?Cart $cart = null, array $shippingAddress = [], array $billingAddress = [], string $formAction = '', ?OrderTransaction $orderTransaction = null): array
    {
        $items = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $data = $cartItem->getData(); 

            $unitAmount = isset($data['unitAmount']) ? $data['unitAmount'] : (
                isset($data['price']) ? $data['price'] : 0
            );

            $items[] = [
                'display_name' => $cartItem->getProduct()->getName(),
                'sku' => $data['sku'],
                'unit_price' => (int) ($unitAmount * 100), 
                'qty' => $cartItem->getQuantity(),
            ];
        }

        $confirmationUrl = $this->urlGenerator->generate('affirm_confirm', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->urlGenerator->generate('affirm_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $total = 0;
        $shippingAmount = 0;
        if ($formAction === 'payment_link' && $orderTransaction?->getAmount()) {
            $total = (int) ($orderTransaction->getAmount() * 100);
        } elseif ($formAction === 'approve_proof' && $order?->getTotalReceivedAmount() >= 0) {
            $amount = round($order->getTotalAmount() + $order->getRefundedAmount() - $order->getTotalReceivedAmount(), 2);
            $total = (int) ( $amount * 100);
        }elseif ($cart?->getTotalAmount()) {
            $total = (int) ($cart->getTotalAmount() * 100);
            $shippingAmount = (int) ($cart->getTotalShipping() * 100);
        }

        return [
            'merchant' => [
                'user_confirmation_url' => $confirmationUrl,
                'user_cancel_url' => $cancelUrl,
                'public_api_key' => $this->publicApiKey,
                'user_confirmation_url_action' => 'POST',
                'name' => 'Yard Sign Plus',
            ],
            'shipping' => $this->formatAffirmAddress($shippingAddress),
            'billing' => $this->formatAffirmAddress($billingAddress),
            'items' => $items,
            'currency' => 'USD',
            'metadata' => [
                'mode' => "modal",
                'env' => $this->env,
                'cart_id' => $cart?->getId() ?? '', 
                'order_id' => $order?->getOrderId() ?? '',
                'form_action' => $formAction ?: '',
                'transaction_id' => $orderTransaction?->getTransactionId() ?? '',
            ],
            'shipping_amount' => $shippingAmount, 
            'tax_amount' => 0, 
            'total' => $total,
        ];
    }

    private function formatAffirmAddress(array $address): array
    {
        return [
            'name' => [
                'first' => $address['firstName'] ?? '',
                'last' => $address['lastName'] ?? '',
            ],
            'address' => [
                'line1' => $address['addressLine1'] ?? '',
                'line2' => $address['addressLine2'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'zipcode' => $address['zipcode'] ?? '',
                'country' => $address['country'] ?? 'US',
            ],
            'email' => $address['email'] ?? '',
            'phone_number' => $address['phone'] ?? '',
        ];
    }
}
