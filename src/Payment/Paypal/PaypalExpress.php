<?php

namespace App\Payment\Paypal;

use App\Entity\Order as OrderEntity;
use App\Payment\PaymentInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaypalExpress extends Order implements PaymentInterface
{

    private ?string $actionOnSuccess = null;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $parameterBag, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($client, $parameterBag, $urlGenerator);
    }

    public function charge(OrderEntity $order, float $customAmount = 0): array
    {
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

        return $this->createOrder($items, true);
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }

}