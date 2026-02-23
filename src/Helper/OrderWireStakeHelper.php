<?php

namespace App\Helper;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Service\CartManagerService;
use App\Service\CartPriceManagerService;
use Doctrine\ORM\EntityManagerInterface;

class OrderWireStakeHelper
{

    static float $wireStakePrice = 0;

    private array $framePricing = [];

    private $variants = [];

    private int $freeShippingDayNumber = 5;

    private array $shippingChart = [];

    private string $wireStakeProductSku = 'WIRE-STAKE';

    private int $maxQty = 9999999999;

    public const DEFAULT_MAXIMUM_DISCOUNT = 50;

    public bool $showSaturdayDelivery = false;

    public function __construct(
        private readonly ShippingChartHelper        $shippingChartHelper,
        private readonly EntityManagerInterface     $entityManager,
        private readonly VichS3Helper               $vichS3Helper,
        private readonly CartManagerService         $cartManager,
        private readonly ProductConfigHelper        $productConfigHelper,
        private readonly PriceChartHelper           $priceChartHelper,
    )
    {
        $productType = $this->getWireStakeProduct()->getProductType();
        $this->variants = $this->getWireStakeProduct()->getVariants();

        $pricing = $productType->getFramePricing();
        $this->framePricing = $this->priceChartHelper->buildFramePricing($pricing);

        $shipping = $productType->getShipping();
        $this->shippingChartHelper->setFreeShippingEnabled(true);
        $baseDate = new \DateTime();
        $dayOfWeek = $baseDate->format('N');
        $timeOfDay = $baseDate->format('H:i:s');
        $isEligible = ($dayOfWeek == 3 && $timeOfDay >= $this->shippingChartHelper->getSaturdayCutOffHour()) || // After cutoff on Wednesday
                        ($dayOfWeek == 4) ||                                            // All day Thursday
                        ($dayOfWeek == 5 && $timeOfDay < $this->shippingChartHelper->getSaturdayCutOffHour());
        $this->showSaturdayDelivery = $isEligible;
        if(!$isEligible) {
            $this->shippingChartHelper->setEnableSorting(false);
        }
        $this->shippingChart = $this->shippingChartHelper->build($shipping, enableDeliveryTiers: false);
    }

    public function getWireStakeProduct(): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $this->wireStakeProductSku]);
    }

    public function getFramePricing(): array
    {
        return $this->framePricing;
    }

    public function getFramePriceFromPriceChart($quantity, $currencyCode = 'usd') {
        foreach($this->framePricing['pricing'] as $pricing){
            if ($quantity >= $pricing['qty']['from'] && $quantity <= $pricing['qty']['to']) {
                return $pricing[$currencyCode];
            }
        }
        return 0;
    }

    public function getShippingByQuantity(int $quantity, array $shipping) : array
    {
        foreach ($shipping as $tier) {
            if ($quantity >= $tier['from'] && $quantity < $tier['to']) {
                return $tier['shippingDates'];
            }
        }
        return $shipping['qty_1']['shippingDates'];
    }

    public function updateShipping(int $quantity = 0) : array {
        $shippingData = [];
        $shippingOptions = [];
        $shipping = $this->getShippingByQuantity($quantity, $this->shippingChart);
        if(!$this->showSaturdayDelivery) {
            array_shift($shipping);
        }
        foreach ($shipping as $tier) {
            $price = 0;
            if(!$tier['free']){
                $pricing = $tier['pricing'][array_key_first($tier['pricing'])];
                $price = $pricing['usd'];
                foreach ($tier['pricing'] as $key => $value) {
                    $qty = intval(substr($key, 4));

                    if ($quantity >= $qty && $quantity < ($qty * 5)) {
                        $price = $value['usd'];
                        $shippingOptions[$tier['date']] = $tier['day'];
                        $shippingData[$tier['date']] = [
                            'day' => $tier['day'],
                            'date' => $tier['date'],
                            'isSaturday' => $tier['isSaturday'],
                            'price' => $value['usd'],
                            'discount' => 0,
                        ];
                    }
                }
            }
            $shippingOptions[$tier['date']] = $tier['day'];
            $shippingData[$tier['date']] = [
                'day' => $tier['day'],
                'isSaturday' => $tier['isSaturday'],
                'date' => $tier['date'],
                'price' => $price,
                'discount' => $tier['discount'] ? $tier['discount'] : 0,
            ];
        }

        return [
            'options' => $shippingOptions,
            'data' => $shippingData,
        ];
    }

    public function getShippingByDay(int|string $day,int $quantity = 0,float $subTotal = 0): array
    {
        $shipping = [
            'day' => $day,
            'isSaturday' => false,
            'date' => '',
            'amount' => 0,
            'discount' => 0,
            'discountAmount' => 0,
        ];
        $shippingChart = $this->getShippingByQuantity($quantity, $this->shippingChart);
        $config = $shippingChart['day_' . $day] ?? null;
        if ($config) {
            $shipping['date'] = $config['date'];
            if (!$config['free']) {
                $pricing = $config['pricing'][array_key_first($config['pricing'])];
                $shipping['amount'] = $pricing['usd'];

                foreach ($config['pricing'] as $key => $value) {
                    $qty = intval(substr($key, 4));
                    $next = next($config['pricing']) ?? null;
                    if ($quantity >= $qty && $quantity < ($next ? $next['qty']['to'] : $this->maxQty)) {
                        $shipping['amount'] = $value['usd'];
                    }
                }
            }
            if($config['discount']){
                $shipping['discount'] = $config['discount'];
                $shipping['discountAmount'] = min($subTotal * ($config['discount']/100), self::DEFAULT_MAXIMUM_DISCOUNT);
            }
        }
        return $shipping;
    }

    public function addToCart(array $data): void
    {
        $editItem = $data['editData'] ? end($data['editData']['items']) ?? null : null;

        $wireStakeProduct = $this->getWireStakeProduct();
        $cart = $this->cartManager->getCart();
        foreach ($data['variants'] as $name => $qty) {
            if ($qty && $qty > 0) {
                $variant = $this->entityManager->getRepository(Product::class)->findVariantByParentAndName($wireStakeProduct, $name);
                if($editItem && $editItem['productId'] === $variant->getId()) {
                    $cartItem = $this->entityManager->getRepository(CartItem::class)->findOneBy(['cart' => $cart, 'itemId' => $editItem['itemId']]);
                } else {
                    $cartItem = new CartItem();
                    $cartItem->setCart($cart);
                    $cartItem->setProduct($variant);
                    $cartItem->setItemId($this->cartManager->generateNewItemId());
                }
                $cartItem->setQuantity($qty);
                $cartItem->setData([
                    "sku" => $variant->getSku(),
                    "name" => $variant->getName(),
                    "image" => $this->vichS3Helper->asset($variant, 'imageFile') ?? 'https://static.yardsignplus.com/assets/grommets-none.png',
                    "price" => self::$wireStakePrice,
                    "addons" => [],
                    "isCustom" => false,
                    "isSample" => false,
                    "isWireStake" => true,
                    "isBlankSign" => false,
                    "quantity" => $qty,
                    "shipping" => $data['shipping'],
                    "template" => "",
                    "productId" => $variant->getId(),
                    "unitAmount" => self::$wireStakePrice,
                    "totalAmount" => $qty * self::$wireStakePrice,
                    "additionalNote" => $data['comment'],
                    "unitAddOnsAmount" => 0,
                ]);
                $cartItem->setDataKey('isBlindShipping', $data['isBlindShipping']);
                $cartItem->setDataKey('deliveryMethod' , $data['toggle'] ? $this->productConfigHelper->getDeliveryMethods()['REQUEST_PICKUP'] : $this->productConfigHelper->getDeliveryMethods()['DELIVERY']);
                $cartItem->setCanvasData(['front' => [], 'back' => []]);
                $cartItem->setShipping($data['shipping']);
                $this->entityManager->persist($cartItem);
                $this->entityManager->flush();
            }
        }
        $this->cartManager->refresh($cart);
    }

}