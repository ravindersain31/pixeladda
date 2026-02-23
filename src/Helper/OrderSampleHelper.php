<?php

namespace App\Helper;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Service\CartManagerService;
use Doctrine\ORM\EntityManagerInterface;

class OrderSampleHelper
{

    static float $yardSignPrice = 0.01;

    static array $shippingConfig = [
        'day_1' => [
            'day' => 1,
            'shipping' => [
                'qty_1' => [
                    'qty' => '1',
                    'usd' => '14.99',
                    'aud' => '14.99',
                ],
            ],
        ],
        'day_3' => [
            'day' => 3,
            'free' => true,
            'shipping' => [
                'qty_1' => [
                    'qty' => '1',
                    'usd' => '0',
                    'aud' => '0',
                ],
            ],
        ],
    ];

    private array $shippingChart = [];

    private string $sampleProductSku = 'SAMPLE';


    public function __construct(
        private readonly ShippingChartHelper    $shippingChartHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly VichS3Helper           $vichS3Helper,
        private readonly CartManagerService     $cartManager,
        private readonly ProductConfigHelper    $productConfigHelper,
        private readonly PriceChartHelper       $priceChartHelper,
    ) {
        $this->shippingChartHelper->setFreeShippingEnabled(false);
        $this->shippingChartHelper->setEnableSorting(false);
        $this->shippingChart = $this->shippingChartHelper->build(self::$shippingConfig);
    }

    public function getSampleProduct(): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $this->sampleProductSku]);
    }

    public function getShippingByQuantity(array $shipping, int $quantity = 1): array
    {
        foreach ($shipping as $tier) {
            if ($quantity >= $tier['from'] && $quantity < $tier['to']) {
                return $tier['shippingDates'];
            }
        }
        return $shipping['qty_1']['shippingDates'];
    }

    public function getShippingOptions(): array
    {
        $shippingOptions = [];
        $shippingData = [];
        $shippingChart = $this->getShippingByQuantity($this->shippingChart);
        $isSaturdayFound = !empty(array_filter($shippingChart, fn($day) => $day['isSaturday'] === true));

        if ($isSaturdayFound) {
            array_shift($shippingChart);
        }

        foreach ($shippingChart as $shipping) {
            $price = 0;
            if (!$shipping['free']) {
                $pricing = $shipping['pricing'][array_key_first($shipping['pricing'])];
                $price = $pricing['usd'];
            }

            $shippingOptions[$shipping['date']] = $shipping['day'];
            $shippingData[$shipping['date']] = [
                'day' => $shipping['day'],
                'isSaturday' => $shipping['isSaturday'],
                'date' => $shipping['date'],
                'price' => $price,
            ];
        }
        return [
            'options' => $shippingOptions,
            'data' => $shippingData,
        ];
    }

    public function getShippingByDay(int $day): array
    {
        $shipping = [
            'day' => $day,
            'isSaturday' => false,
            'date' => '',
            'amount' => 0,
            'discount' => 0,
            'discountAmount' => 0,
        ];
        $shippingChart = $this->getShippingByQuantity($this->shippingChart);
        $config = $shippingChart['day_' . $day] ?? null;
        if ($config) {
            $shipping['date'] = $config['date'];
            if (!$config['free']) {
                $pricing = $config['pricing'][array_key_first($config['pricing'])];
                $shipping['amount'] = $pricing['usd'];
            }
        }
        return $shipping;
    }

    public function getFreeShipping(): array
    {
        $shippingOptions = $this->getShippingOptions();

        foreach ($shippingOptions['data'] as $option) {
            if (isset($option['price']) && (float)$option['price'] === 0.0) {
                $option['free'] = true;
                return $option;
            }
        }

        return [];
    }

    public function addToCart(array $data): void
    {

        $editItem = $data['editData'] ? end($data['editData']['items']) ?? null : null;

        $sampleProduct = $this->getSampleProduct();
        $cart = $this->cartManager->getCart();
        $deliveryMethod = $data['toggle'] ? $this->productConfigHelper->getDeliveryMethods()['REQUEST_PICKUP'] : $this->productConfigHelper->getDeliveryMethods()['DELIVERY'];
        $shipping = $data['shipping'];
        if ($deliveryMethod && $deliveryMethod['discount'] > 0) {
            $discount = (float) $deliveryMethod['discount'];
            $data['shipping']['amount'] *= (1 - $discount / 100);
            $shipping['amount'] = (float) bcdiv($data['shipping']['amount'], 1, 2);
        }


        foreach ($data['variants'] as $variantName => $qty) {
            if ($qty && $qty > 0) {
                $variant = $this->entityManager->getRepository(Product::class)->findOneBy(['parent' => $sampleProduct, 'slug' => $variantName]);
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
                    "price" => self::$yardSignPrice,
                    "addons" => $data['addons'],
                    "isCustom" => false,
                    "isSample" => true,
                    "quantity" => $qty,
                    "shipping" => $data['shipping'],
                    "template" => "",
                    "productId" => $variant->getId(),
                    "unitAmount" => self::$yardSignPrice,
                    "totalAmount" => $qty * self::$yardSignPrice,
                    "additionalNote" => $data['comment'],
                    "unitAddOnsAmount" => 0,
                ]);
                $cartItem->setDataKey('isBlindShipping', $data['isBlindShipping']);
                $cartItem->setDataKey('deliveryMethod', $deliveryMethod);
                $cartItem->setCanvasData(['front' => [], 'back' => []]);
                $cartItem->setShipping($shipping);
                $this->entityManager->persist($cartItem);
                $this->entityManager->flush();
            }
        }

        if($data['customSize'] || isset($editItem['data']['customSize'])){
            foreach ($data['customSize'] as $customSizeData) {
                if ($customSizeData['quantity'] > 0) {
                    $templateSize = $customSizeData['width'] . 'x' . $customSizeData['height'];
                    $variant = $this->entityManager->getRepository(Product::class)->findOneBy(['parent' => $sampleProduct]);
                    $product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => 'CUSTOM-SIZE', 'isCustomSize' => true]);
                    $activeVariants = $this->entityManager->getRepository(Product::class)->findActiveVariants($product);
                    $customSizeVariant = end($activeVariants);
                    $variants = $this->priceChartHelper->getVariantsFromPricing($product->getProductType()->getPricingAll());
                    $closestVariant = $this->priceChartHelper->getClosestVariant($templateSize, $variants);
                    $isFreeFreight = false; 
                    
                    if($this->isBiggerSize((int) $customSizeData['width'], (int) $customSizeData['height']) && $data['isFreeFreight']){
                        $isFreeFreight = $data['isFreeFreight']; 
                    }

                    if ($editItem && $editItem['productId'] === $customSizeVariant->getId()) {
                        $cartItem = $this->entityManager->getRepository(CartItem::class)->findOneBy(['cart' => $cart, 'itemId' => $editItem['itemId']]);
                    } else {
                        $cartItem = new CartItem();
                        $cartItem->setCart($cart);
                        $cartItem->setProduct($customSizeVariant);
                        $cartItem->setItemId($this->cartManager->generateNewItemId());
                    }

                    $cartItem->setQuantity($customSizeData['quantity']);
                    $cartItem->setData([
                        "sku" => $customSizeVariant->getSku(),
                        "name" => $templateSize,
                        "image" => $this->vichS3Helper->asset($variant, 'imageFile') ?? 'https://static.yardsignplus.com/assets/grommets-none.png',
                        "price" => self::$yardSignPrice,
                        "addons" => $data['addons'],
                        "isCustom" => false,
                        "isSample" => true,
                        "isCustomSize" => true,
                        'customSize' => [
                            "sku" => $variant->getSku(),
                            "image" => $this->vichS3Helper->asset($variant, 'imageFile') ?? 'https://static.yardsignplus.com/assets/grommets-none.png',
                            "category" => $variant->getParent()->getPrimaryCategory()->getName(),
                            "productId" =>  $variant->getId(),
                            "parentSku" => $variant->getParent()->getSku(),
                            "isCustomSize" => true,
                            "isSample" => true,
                            "templateSize" => [
                                "width" => $customSizeData['width'],
                                "height" => $customSizeData['height'],
                            ],
                            "closestVariant" => $closestVariant,
                        ],
                        "quantity" => $customSizeData['quantity'],
                        "shipping" => $data['shipping'],
                        "template" => "",
                        "productId" => $customSizeVariant->getId(),
                        "templateSize" => [
                            "width" => $customSizeData['width'],
                            "height" => $customSizeData['height'],
                        ],
                        "unitAmount" => self::$yardSignPrice,
                        "totalAmount" => $customSizeData['quantity'] * self::$yardSignPrice,
                        "additionalNote" => $data['comment'],
                        "unitAddOnsAmount" => 0,
                        "isFreeFreight" => $isFreeFreight
                    ]);

                    $cartItem->setDataKey('isBlindShipping', $data['isBlindShipping']);
                    $cartItem->setDataKey('deliveryMethod', $deliveryMethod);
                    $cartItem->setCanvasData(['front' => [], 'back' => []]);
                    $cartItem->setShipping($shipping);
                    $this->entityManager->persist($cartItem);
                    $this->entityManager->flush();
                }
            }
        }

        $this->cartManager->refresh($cart);
    }

    private function isBiggerSize(int $width, int $height, int $refWidth = 48, int $refHeight = 24): bool {
        $fitsInOrientation1 = $width <= $refWidth && $height <= $refHeight;
        $fitsInOrientation2 = $width <= $refHeight && $height <= $refWidth;
    
        return !($fitsInOrientation1 || $fitsInOrientation2);
    }
}