<?php

namespace App\Payment\Paypal;

use App\Payment\PaymentInterface;
use App\Entity\Order as OrderEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Paypal extends Order implements PaymentInterface
{
    private ?string $actionOnSuccess = null;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $parameterBag, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($client, $parameterBag, $urlGenerator);
    }

    public function charge(OrderEntity $order, float $customAmount = 0): array
    {
        $shippingAddress = $order->getShippingAddress();
        $this->setCustomerName([
            'first_name' => $shippingAddress['firstName'],
            'last_name' => $shippingAddress['lastName'],
        ]);

        $this->setShippingAddress([
            'address_line_1' => $shippingAddress['addressLine1'],
            'address_line_2' => $shippingAddress['addressLine2'],
            'admin_area_2' => $shippingAddress['city'],
            'admin_area_1' => $shippingAddress['state'],
            'postal_code' => $shippingAddress['zipcode'],
            'country_code' => $shippingAddress['country'],
        ]);

        $items = [];
        if ($customAmount > 0) {
            $items[] = [
                'name' => 'Yard Sign',
                'description' => 'Yard Sign - Custom Amount Charge',
                'price' => $customAmount,
                'quantity' => 1,
            ];
        } else {
            foreach ($order->getOrderItems() as $item) {
                $template = $item->getProduct();
                $product = $template->getParent();
                if($item->getMetaDataKey('isCustomSize')){
                    $name = 'CUSTOM-SIZE - '.($item->getMetaDataKey('customSize')['templateSize']['width'].'x'.$item->getMetaDataKey('customSize')['templateSize']['height']);
                }else{
                    $name = $template->getName();
                }
                $items[] = [
                    'name' => $name,
                    'description' => $product->getName(),
                    'price' => $item->getUnitAmount(),
                    'quantity' => $item->getQuantity(),
                ];
            }

            $discount = 0;
            $additionalDiscounts = $order->getAdditionalDiscount();

            if (is_array($additionalDiscounts)) {
                foreach ($additionalDiscounts as $discountData) {
                    if (isset($discountData['amount']) && $discountData['amount'] > 0) {
                        $discount += $discountData['amount'];
                    }
                }
            }

            $this->setShippingAmount($order->getShippingAmount());
            $this->setHandlingAmount($order->getOrderProtectionAmount());
            $this->setDiscountAmount($order->getCouponDiscountAmount() + $discount);
        }

        $urlParams = ['orderId' => $order->getOrderId()];
        if($this->actionOnSuccess) {
            $urlParams['actionOnSuccess'] = $this->actionOnSuccess;
        }

        $returnUrl = $this->urlGenerator->generate('paypal_return', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->urlGenerator->generate('paypal_cancel', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $this->setRedirectUrls($returnUrl, $cancelUrl);

        return $this->createOrder($items);
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }

}