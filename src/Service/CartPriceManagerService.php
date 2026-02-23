<?php

namespace App\Service;

use App\Constant\Editor\Addons;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Helper\OrderSampleHelper;
use App\Helper\ProductConfigHelper;
use App\Helper\ShippingChartHelper;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\Reward\RewardService;
use App\Service\StoreInfoService;
use App\Twig\AppExtension;

class CartPriceManagerService
{
    private Cart $cart;

    private array $store;

    private ?SessionInterface $session = null;

    private array $earliestShipping = [
        'itemId' => null,
        'shipping' => [
            'day' => 0,
            'date' => '',
            'amount' => 0,
            'discount' => 0,
            'discountAmount' => 0,
        ],
    ];

    public const YSP_LOGO_DISCOUNT = 5;
    public const YSP_MAX_DISCOUNT_AMOUNT = 25;

    public const PRE_PACKED_DISCOUNT = 20;
    public const PRE_PACKED_MAX_DISCOUNT_AMOUNT = 100;

    private array $YSPLogoDiscount = [
        'hasLogo' => false,
        'type' => 'PERCENTAGE',
        'discount' => self::YSP_LOGO_DISCOUNT,
        'discountAmount' => 0,
    ];

    private array $prePackedDiscount = [
        'hasPrePacked' => false,
        'type' => 'PERCENTAGE',
        'discount' => self::PRE_PACKED_DISCOUNT,
        'discountAmount' => 0,
    ];

    private bool $isIsSample = false;
    private bool $isBlindShipping = false;
    private bool $isFreeFreight = false;
    private bool $isClone = false;

    public const DEFAULT_MAXIMUM_DISCOUNT = 50;
    public const DEFAULT_MAXIMUM_DISCOUNT_10 = 100;

    public const SUB1500_CODE = 'SUB1500';
    public const SUB1500_BASE_LIMIT = 1500;
    public const SUB1500_MAX_DISCOUNT = 100;
    public const SUB1500_NAME = 'Free items $1500+';

    public function __construct(
        RequestStack                            $requestStack,
        private readonly ShippingChartHelper    $shippingChartHelper,
        private readonly ProductConfigHelper    $productConfigHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly RewardService          $rewardService,
        private readonly Addons                 $addons,
        private readonly StoreInfoService       $storeInfoService,
        private readonly AppExtension           $appExtension,
    )
    {
        $request = $requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $this->store = $request->get('store') ?? [];
            $this->session = $request->getSession();
        }
    }

    public function recalculateCartPrice(Cart $cart, bool $isClone = false): ?Cart
    {
        $this->isClone = $isClone;
        $this->cart = $cart;
        $items = $cart->getCartItems();
        $this->recalculateCartQuantity($items);
        $this->recalculateItems($items);
        $this->recalculateTotals();
        if ($this->cart->getVersion() === 'V2') {
            $this->recalculateShipping($items);
        }
        $this->recalculateAdditionalDiscounts($items, $cart);
        $this->recalculateNeedProof($cart);
        $this->entityManager->persist($this->cart);
        $this->entityManager->flush();
        return $this->cart;
    }

    public function recalculateTotals(): void
    {
        $subTotalAmount = $this->cart->getSubTotal();
        $totalAmount = $subTotalAmount;
        $shippingAmount = $this->cart->getTotalShipping();
        $totalAmount += $shippingAmount;

        $internationalShippingCharge = $this->cart->isInternationalShippingCharge();
        if ($internationalShippingCharge) {
            $internationalShippingChargeAmount = $this->cart->getInternationalShippingChargeAmount();
            $totalAmount += $internationalShippingChargeAmount;
        }

        $orderProtection = $this->cart->isOrderProtection();
        $orderProtectionAmount = 0;
        if ($orderProtection) {
            $applyOrderProtectionAmount = $subTotalAmount + $shippingAmount;
            $orderProtectionAmount = round($applyOrderProtectionAmount * $this->cart::ORDER_PROTECTION_PERCENTAGE / 100, 2);
            $this->cart->setOrderProtectionAmount($orderProtectionAmount);
        }
        $totalAmount -= $this->cart->getCouponAmount();
        $totalAmount += $orderProtectionAmount;
        $this->cart->setTotalAmount($totalAmount);
    }

    public function recalculateShipping(Collection $items): void
    {
        $this->isSample();
        /** @var CartItem $item */
        foreach ($items as $item) {
            if ($item->getDataKey('isSample')) {
                $this->handleSampleShipping($item);
            } else {
                $this->handleRegularShipping($item);
            }
        }

        $this->cart->setTotalShipping($this->earliestShipping['shipping']['amount'] ?? 0);
        $this->cart->setDataKey('shipping', $this->earliestShipping['shipping']);
        $this->cart->setDataKey('shippingItemId', $this->earliestShipping['itemId']);
        $this->cart->setDataKey('isBlindShipping', $this->isBlindShipping);
        $this->cart->setDataKey('isFreeFreight', $this->isFreeFreight);

        $this->recalculateTotals();
    }

    public function handleSampleShipping(CartItem $item): void
    {
        $shipping = $item->getShipping();

        $item->setShipping($shipping);
        $item->setDataKey('shipping', $shipping);
        $this->updateEarliestShipping($item, $shipping);
    }

    public function handleRegularShipping(CartItem $item): void
    {
        $subTotal = $item->getCart()->getSubTotal();

        if (floatval($subTotal) < floatval(50)) {
            $this->shippingChartHelper->setFreeShippingEnabled(false);
        } else {
            $isFreeShippingEnabled = (float)$subTotal >= $this->shippingChartHelper->minAmountForFreeShipping;
            $this->shippingChartHelper->setFreeShippingEnabled($isFreeShippingEnabled);
        }

        $shipping = $this->getShippingPrice($this->cart->getTotalQuantity(), $item, $item->getShipping());
        $shipping = $this->applyDeliveryMethodDiscount($item, $shipping);

        $item->setShipping($shipping);
        $item->setDataKey('shipping', $shipping);
        $this->updateEarliestShipping($item, $shipping);
    }

    public function updateEarliestShipping(CartItem $item, array $shipping): void
    {
        if(!$this->isIsSample && $item->getDataKey('isSample')){
            return;
        }

        if(floatval($this->earliestShipping['shipping']['amount'] ?? 0) <= floatval($shipping['amount'])){
            $this->earliestShipping = [
                'itemId' => $item->getItemId(),
                'shipping' => $shipping,
            ];
            $this->isBlindShipping = $item->getDataKey('isBlindShipping') ?? false;
            $this->isFreeFreight = $item->getDataKey('isFreeFreight') ?? false;
            $this->setDeliveryMethod($item);
        }
    }

    public function isSample(): bool
    {
        $cartItems = $this->cart->getCartItems();

        $nonSampleItems = array_filter($cartItems->toArray(), function ($item) {
            $template = $item->getProduct()->getParent()->getSku();
            return $this->isIsSample = $template !== 'SAMPLE' && ($item->getDataKey('customSize')['parentSku'] ?? null) !== 'SAMPLE';
        });

        return $this->isIsSample = empty($nonSampleItems);
    }

    private function applyDeliveryMethodDiscount($item, array $shipping): array
    {
        $deliveryMethod = $item->getDataKey('deliveryMethod');
        if ($deliveryMethod && $deliveryMethod['discount'] > 0) {
            $discount = (float)$deliveryMethod['discount'];
            $shipping['amount'] *= (1 - $discount / 100);
            $shipping['amount'] = (float)bcdiv($shipping['amount'], 1, 2);
        }
        return $shipping;
    }

    private function setDeliveryMethod($item): void
    {
        $deliveryMethod = $item->getDataKey('deliveryMethod');
        $this->cart->setDataKey('deliveryMethod', $deliveryMethod);
    }


    public function recalculateItems(Collection $items): void
    {
        $biggerSizes = [];
        $quantityBySizes = $this->cart->getDataKey('quantityBySizes');
        $totalFrameQuantity = $this->cart->getDataKey('totalFrameQuantity');
        $subTotalAmount = 0;
        /** @var CartItem $item */
        foreach ($items as $item) {
            $variant = $item->getProduct();
            $isYardLetters = $item->getProduct()->getParent()->getProductType()->getSlug() == 'yard-letters';
            $prePackedFrameTypes = $item->getProduct()->getParent()->getProductMetaDataKey('frameTypes');
            $prePackedTotalSigns = $item->getProduct()->getParent()->getProductMetaDataKey('totalSigns');
            $prePackedSigns = $item->getProduct()->getParent()->getProductImages();
            $data = $item->getData();
            if ($item->getDataKey('isCustomSize')) {
                $quantity = $quantityBySizes['CUSTOM_' . $data['customSize']['closestVariant']] ?? $item->getQuantity();
            } else {
                $quantity = $quantityBySizes[$variant->getName()] ?? $item->getQuantity();
            }
            if ($this->cart->getVersion() === 'V1') {
                $price = $item->getDataKey('price');
            } else if ($item->getDataKey('isSample')) {
                $price = OrderSampleHelper::$yardSignPrice;
            } else {
                $price = $this->getPrice($variant, $quantity, $item);
                if($item->getDataKey('isWireStake')){
                    $price = $this->getFrameTypePrice($item->getProduct(), $totalFrameQuantity[$item->getProduct()->getName()] ?? $quantity, $item->getProduct()->getName());
                }
            }

            $itemBasePrice = $price;
            if($isYardLetters){
                $itemBasePrice = ($price) * ($prePackedTotalSigns);
            }

            $item->setDataKey('price', $price);

            $unitAddOnsAmount = 0;
            $addons = [];
            foreach ($item->getDataKey('addons') as $key => $addon) {
                if ($this->addons->hasSubAddon($addon)) {
                    foreach ($addon as $subAddonKey => $subAddonValue) {
                        $addonAmount = $this->addons->getAddOnPricesByKey($key)[$subAddonValue['key']];
                        if ($subAddonValue['type'] === 'PERCENTAGE') {
                            $subAddonValue['unitAmount'] = self::toFixed($price * $addonAmount / 100);
                        } else if ($subAddonValue['type'] === 'FIXED') {
                            $subAddonValue['unitAmount'] = $subAddonValue['amount'];
                        }
                        if ($subAddonValue['key'] !== "NONE" && $key === 'frame') {
                            $addonAmount = $this->getFrameTypePrice($item->getProduct(), $totalFrameQuantity[$subAddonValue['key']], $subAddonValue['key']);
                            $subAddonValue['unitAmount'] = round($addonAmount * ($isYardLetters && $prePackedFrameTypes ? $prePackedFrameTypes[$subAddonValue['key']] : count($prePackedSigns)), 2);
                            $subAddonValue['amount'] = round($addonAmount, 2);
                        }
                        $unitAddOnsAmount += $subAddonValue['unitAmount'];
                        if ($key === 'frame' && isset($addons[$key])) {
                            $addons[$key][$subAddonKey] = array_merge($addons[$key][$subAddonKey] ?? [], $subAddonValue);
                        } else {
                            $addons[$key][$subAddonKey] = $subAddonValue;
                        }
                    }
                } else {
                    $addonAmount = $this->addons->getAddOnPricesByKey($key)[$addon['key']];
                    if ($addon['type'] === 'PERCENTAGE') {
                        $addon['unitAmount'] = self::toFixed($itemBasePrice * $addonAmount / 100);
                    } else if ($addon['type'] === 'FIXED') {
                        $addon['unitAmount'] = $addon['amount'];
                    }
                    if ($addon['key'] !== "NONE" && $key === 'frame') {
                        $addonAmount = $this->getFrameTypePrice($item->getProduct(), $totalFrameQuantity[$addon['key']] ?? $item->getQuantity(), $addon['key']);
                        $addon['unitAmount'] = round($addonAmount, 2);
                        $addon['amount'] = round($addonAmount, 2);
                    }
                    $unitAddOnsAmount += $addon['unitAmount'];
                    if ($key === 'frame' && isset($addons[$key])) {
                        $addons[$key] = array_merge($addons[$key], $addon);
                    } else {
                        $addons[$key] = $addon;
                    }
                }
            }

            $item->setDataKey('addons', $addons);

            $item->setDataKey('unitAddOnsAmount', $unitAddOnsAmount);
            
            if($isYardLetters){
                $unitAmount = ($price * $prePackedTotalSigns)  + $unitAddOnsAmount;
            }else {
                $unitAmount = $price + $unitAddOnsAmount;
            }

            $item->setDataKey('unitAmount', $unitAmount);
            $totalAmount = $unitAmount * $item->getQuantity();

            if (!isset($data['YSPLogoDiscount']) || !is_array($data['YSPLogoDiscount'])) {
                $data['YSPLogoDiscount'] = $this->YSPLogoDiscount;
            }

            if (!isset($data['prePackedDiscount']) || !is_array($data['prePackedDiscount'])) {
                $data['prePackedDiscount'] = $this->prePackedDiscount;
            }

            if (!isset($data['customArtwork']) || !is_array($data['customArtwork'])) {
                $item->setDataKey('customArtwork', []);
            } else {
                $item->setDataKey('customArtwork', $data['customArtwork']);
            }

            if (!isset($data['customOriginalArtwork']) || !is_array($data['customOriginalArtwork'])) {
                $item->setDataKey('customOriginalArtwork', []);
            } else {
                $item->setDataKey('customOriginalArtwork', $data['customOriginalArtwork']);
            }

            $item->setDataKey('unitAmount', $unitAmount);
            $item->setDataKey('totalAmount', $totalAmount);

            $subTotalAmount += $totalAmount;

            $customSize = $item->getDataKey('customSize');
            if ($customSize && isset($customSize['templateSize'])) {
                $isBiggerSize = $this->isBiggerSize($customSize['templateSize']);
                $item->setDataKey('isBiggerSize', $isBiggerSize);
                if ($isBiggerSize) {
                    $biggerSizes[$item->getId()] = $item->getItemId();
                }
            }
        }
        $this->cart->setDataKey('hasBiggerSizes', count($biggerSizes) > 0);
        $this->cart->setDataKey('biggerSizes', $biggerSizes);

        $this->cart->setSubTotal($subTotalAmount);
        $this->updateOrderProtection($subTotalAmount);
    }

    private function getFrameTypePrice(Product $variant, int $totalFrameQuantity, string $frameType): float
    {
        return $this->getFramePrice($variant, $totalFrameQuantity, $frameType);
    }


    private function updateOrderProtection($subTotalAmount): void
    {
        if ($subTotalAmount >= 1000) {
            $this->cart->setOrderProtection(false);
            $this->cart->setOrderProtectionAmount(0);
        } else {
            if ($this->session instanceof SessionInterface && $this->session->get('orderProtection')) {
                $this->cart->setOrderProtection(true);
                $this->cart->setOrderProtectionAmount(round($subTotalAmount * 15 / 100, 2));
                $this->session->set('orderProtection', true);
            }
        }
    }


    function toFixed($number, $decimals = 2): float
    {
        return (float) number_format(round($number, $decimals), $decimals, '.', '');
    }


    public function recalculateCartQuantity(Collection $items): void
    {
        $quantityBySizes = [];
        $totalQuantity = 0;
        $totalFrameQuantity = [];
        $quantityBySizes['CUSTOM'] = 0;
        $quantityBySizes['SAMPLE'] = 0;

        foreach ($items as $item) {
            $isYardLetters = $item->getProduct()->getParent()->getProductType()->getSlug() == 'yard-letters';
            $data = $item->getData();
            $template = $item->getProduct();
            if (!isset($quantityBySizes[$template->getName()])) {
                $quantityBySizes[$template->getName()] = 0;
            }
            if($item->getDataKey('isWireStake')) {
                if(!isset($totalFrameQuantity[$template->getName()])) {
                    $totalFrameQuantity[$template->getName()] = 0;
                }
                $totalFrameQuantity[$template->getName()] += $item->getQuantity();
            }
            if (!$item->getDataKey('isSample') && !$item->getDataKey('isCustomSize')) {
                if($isYardLetters) {
                    $quantityBySizes[$template->getName()] += $item->getQuantity() * $item->getProduct()->getParent()->getProductMetaDataKey('totalSigns');
                }else{
                    $quantityBySizes[$template->getName()] += $item->getQuantity();
                }
            }
            if ($item->getDataKey('isSample')) {
                $quantityBySizes['SAMPLE'] += $item->getQuantity();
            }
            if ($item->getDataKey('isCustomSize')) {
                $customKey = 'CUSTOM_' . $data['customSize']['closestVariant'] ?? 'CUSTOM';
                if (!isset($quantityBySizes[$customKey])) {
                    $quantityBySizes[$customKey] = 0;
                }
                $quantityBySizes[$customKey] += $item->getQuantity();
            }
            foreach ($data['addons'] as $key => $addon) {
                if ($this->addons->hasSubAddon($addon) && $key === 'frame') {
                    foreach ($addon as $subAddonKey => $subAddonValue) {
                        if (
                            isset($data['customSize']['closestVariant']) &&
                            isset($subAddonValue['displayText']) &&
                            $subAddonValue['displayText'] !== 'No Frame'
                        ) {
                            $frameKey = $subAddonValue['key'] ?? $subAddonKey;
                            $frameQuantity = $isYardLetters ? ($subAddonValue['quantity'] ?? $subAddonValue['totalQuantity']) * $item->getQuantity() : ($subAddonValue['quantity'] ?? $subAddonValue['totalQuantity'] ?? $item->getQuantity());
                            $totalFrameQuantity[$frameKey] = ($totalFrameQuantity[$frameKey] ?? 0) + $frameQuantity;
                        }
                    }
                } else {
                    if (
                        isset($data['customSize']['closestVariant']) &&
                        isset($data['addons']['frame']['displayText']) &&
                        $data['addons']['frame']['displayText'] !== 'No Frame' &&
                        $key === 'frame'
                    ) {
                        $frameKey = $data['addons']['frame']['key'];
                        $frameQuantity = $data['addons']['frame']['quantity'] ?? $data['addons']['frame']['totalQuantity'] ?? $item->getQuantity();
                        $totalFrameQuantity[$frameKey] = ($totalFrameQuantity[$frameKey] ?? 0) + $frameQuantity;
                    }
                }
            }
            $totalQuantity += $item->getQuantity();
        }
        $this->cart->setDataKey('totalFrameQuantity', $totalFrameQuantity);
        $this->cart->setDataKey('quantityBySizes', $quantityBySizes);
        $this->cart->setDataKey('totalQuantity', $totalQuantity);
        $this->cart->setTotalQuantity($totalQuantity);
    }

    public function checkFrameSize(string|array $templateSize): bool
    {
        $templateSizeArray = is_array($templateSize) ? $templateSize : $this->parseTemplateSize($templateSize);

        return $templateSizeArray['width'] > 9;
    }


    private function parseTemplateSize(string $templateSize): array
    {
        $widthAndHeight = explode('x', $templateSize);
        return [
            'width' => intval($widthAndHeight[0]) ?: 12,
            'height' => intval($widthAndHeight[1]) ?: 12,
        ];
    }

    public function getPrice(Product $variant, $quantity, CartItem|null $cartItem = null): float
    {
        $currency = $this->store['currencyCode'] ?? 'USD';
        $product = $variant->getParent();
        $pricing = $this->productConfigHelper->getPriceChart($product);
        $framePricing = $this->productConfigHelper->getFramePriceChart($product);

        if ($variant->getParent()->getSku() === 'WIRE-STAKE' && $cartItem->getDataKey('isWireStake')) {
            $pricingChart = $framePricing['frames']['pricing_'. $variant->getName()]['pricing'];
        } else {
            if ($variant->isIsCustomSize() && isset($cartItem->getData()['customSize'])) {
                $closestSize = $cartItem->getData()['customSize']['closestVariant'];
                $pricingChart = $pricing['variants']['pricing_' . $closestSize]['pricing'];
            } else {
                $pricingChart = $pricing['variants']['pricing_' . $variant->getName()]['pricing'];
            }
        }
        foreach ($pricingChart as $price) {
            if ($quantity >= $price['qty']['from'] && $quantity <= $price['qty']['to']) {
                return $price[strtolower($currency)] ?? 0;
            }
        }
        return 0;
    }

    private function getShippingPrice(int|string $quantity, CartItem $item, array|null $shipping = null): array
    {
        $currency = $this->store['currencyCode'] ?? 'USD';
        $baseDate = new \DateTime();
        $dayOfWeek = $baseDate->format('N');
        $timeOfDay = $baseDate->format('H:i:s');
        $showSaturdayDelivery = ($dayOfWeek == 3 && $timeOfDay >= $this->shippingChartHelper->getSaturdayCutOffHour()) || // After cutoff on Wednesday
                        ($dayOfWeek == 4) ||                                                                              // All day Thursday
                        ($dayOfWeek == 5 && $timeOfDay < $this->shippingChartHelper->getSaturdayCutOffHour());            // Before friday cutoff

        $productType = $this->entityManager->getRepository(ProductType::class)->findBySlug('yard-sign');
        $shippings = $productType->getShipping();
        $productSku = $item->getProduct()->getParent()->getSku();
        if ($productSku === "WIRE-STAKE") {
            $shippingChart = $this->shippingChartHelper->build($shippings, enableDeliveryTiers: false);
        }else{
            $shippingChart = $this->shippingChartHelper->build($shippings, enableDeliveryTiers: false);
        }
        $shippingChart = $this->shippingChartHelper->getShippingByQuantity($quantity, $shippingChart);
        $selectedDay = $shippingChart['day_' . $shipping['day']] ?? [];
        $isSaturdaySelected = !empty($selectedDay['isSaturday']) && $selectedDay['isSaturday'] === true;
        $eligibleForSaturday = $this->shippingChartHelper->checkSaturdayDeliveryEligibility();

        // if(isset($shipping['isSaturday'])) {
        //     if(!$showSaturdayDelivery && $shipping['isSaturday']) {
        //         $selectedDay = reset($shippingChart);
        //     }
        // }
        if (count($selectedDay) <= 0) {
            $selectedDay = end($shippingChart);
            $selectedDay = prev($shippingChart);
        }
        if(!$eligibleForSaturday && $isSaturdaySelected) {
            $baseDate = new \DateTime($selectedDay['date']);
            $selectedDay = $this->shippingChartHelper->getNextShippingDay($shippingChart, $baseDate);
        }

        if (!$selectedDay['free']) {
            foreach ($selectedDay['pricing'] as $price) {
                if ($quantity >= $price['qty']['from'] && $quantity <= $price['qty']['to']) {
                    $price = $price[strtolower($currency)] ?? 0;
                    // $isBiggerSize = $item->getDataKey('isBiggerSize');
                    // if ($isBiggerSize) {
                    //     $price *= 2;
                    // }
                    return [
                        'day' => $selectedDay['day'],
                        'isSaturday' => $selectedDay['isSaturday'],
                        'date' => $selectedDay['date'],
                        'amount' => $price,
                        'discount' => 0,
                        'discountAmount' => 0,
                    ];
                }
            }
        }

        return [
            'day' => $selectedDay['day'],
            'isSaturday' => $selectedDay['isSaturday'],
            'date' => $selectedDay['date'],
            'amount' => 0,
            'discount' => $selectedDay['discount'],
            'discountAmount' => $this->calculateDiscountShipping($selectedDay["discount"]),
        ];
    }

    public function getFramePrice(Product $variant, int $quantity, string $frameType): float
    {
        $currency = $this->store['currencyCode'] ?? 'USD';
        $product = $variant->getParent();
        $framePricing = $this->productConfigHelper->getFramePriceChart($product);

        if($variant->getParent()->getSku() === 'WIRE-STAKE') {
            $framePriceChart = $framePricing['frames']['pricing_'. $variant->getName()]['pricing'];
        }else{
            $framePriceChart = $framePricing['frames']['pricing_'. $frameType]['pricing'];
        }

        foreach ($framePriceChart as $framePrice) {
            if ($quantity >= $framePrice['qty']['from'] && $quantity <= $framePrice['qty']['to']) {
                return $framePrice[strtolower($currency)] ?? 0;
            }
        }
        return 0;
    }

    private function calculateDiscountShipping(float $discount): float
    {
        $cartSubTotal = $this->cart->getSubTotal();
        $maxCap = $this->getMaxDiscountCap($discount);
        $discountAmount = min($cartSubTotal * $discount / 100, $maxCap);
        return (float)number_format($discountAmount, 2, '.', '');
    }

    private function getMaxDiscountCap(float $discount): float
    {
        return $discount <= 5 ? self::DEFAULT_MAXIMUM_DISCOUNT : self::DEFAULT_MAXIMUM_DISCOUNT_10;
    }

    private function recalculateAdditionalDiscounts(Collection $items, $cart): void
    {
        if ($this->isClone) {
            $this->cart->setAdditionalDiscount([]);
            return;
        }

        $this->applyShippingDiscount($items, $cart);
        // $this->applySubTotalDiscount();
        $this->applySub1500Discount();
        $this->applyRewardDiscount();
        $this->applyYSPLogoDiscount($items, $cart);
        $this->applyPrePackedDiscount($items, $cart);
    }

    private function applyShippingDiscount($items, $cart): void
    {
        $earliestDate = null;
        $earliestShipping = null;
        foreach ($items as $item) {
            $shipping = $item->getData()['shipping'] ?? null;

            if ($shipping && isset($shipping['date'])) {
                $date = new \DateTime($shipping['date']);

                if (is_null($earliestDate) || $date < $earliestDate) {
                    $earliestDate = $date;
                    $earliestShipping = $shipping;
                }
            }
        }
        if ($earliestShipping) {
            $cart->setDataKey('shipping', $earliestShipping);
        }
        
        $shipping = $this->cart->getDataKey('shipping');
        $discountAmount = $shipping['discountAmount'] ?? 0;
        $discount = $shipping['discount'] ?? 0;
            if (isset($shipping) && $discountAmount > 0) {
                $this->cart->setAdditionalDiscountKey(key: 'shippingDiscount', name: 'Shipping Discount ' . $discount . '% OFF', amount: $discountAmount);
                $this->updateTotalAmount($shipping['discountAmount']);
            } else {
                $this->cart->removeAdditionalDiscountKey('shippingDiscount');
            }
    }

    private function applyYSPLogoDiscount(Collection $items , $cart): void
    {
        $subTotal = 0;
        $totalDiscountAmount = 0;
        $hasYspLogo = false;

        foreach ($items as $item) {
            if (isset($item->getDataKey('YSPLogoDiscount')['hasLogo']) && $item->getDataKey('YSPLogoDiscount')['hasLogo']) {
                $subTotal += $item->getDataKey('totalAmount');
                $hasYspLogo = true;
            }
        }

        if($hasYspLogo){
            $totalDiscountAmount = round(($subTotal * self::YSP_LOGO_DISCOUNT) / 100, 2);

          /*   $total = $cart->getTotalAmount();
            if ($total > 0) {
                $totalDiscountAmount = round(($total * self::YSP_LOGO_DISCOUNT) / 100, 2);
            } else {
                $totalDiscountAmount = round(($subTotal * self::YSP_LOGO_DISCOUNT) / 100, 2);
            } */
            $logoDiscountText =  $this->storeInfoService->storeInfo()["logoDiscountText"];
            $totalDiscountAmount = min($totalDiscountAmount, self::YSP_MAX_DISCOUNT_AMOUNT);
            $this->cart->setAdditionalDiscountKey(key: 'YSPLogoDiscount', name: $logoDiscountText, amount: $totalDiscountAmount);
            $this->updateTotalAmount($totalDiscountAmount);
        } else {
            $this->cart->removeAdditionalDiscountKey('YSPLogoDiscount');
        }
    }
    private function applyPrePackedDiscount(Collection $items , $cart): void
    {
        $subTotal = 0;
        $totalDiscountAmount = 0;
        $hasPrePacked = false;

        foreach ($items as $item) {
            if (isset($item->getDataKey('prePackedDiscount')['hasPrePacked']) && $item->getDataKey('prePackedDiscount')['hasPrePacked']) {
                $subTotal += $item->getDataKey('totalAmount');
                $hasPrePacked = true;
            }
        }

        if($hasPrePacked){
            $totalDiscountAmount = round(($subTotal * self::PRE_PACKED_DISCOUNT) / 100, 2);
            $this->cart->setAdditionalDiscountKey(key: 'prePackedDiscount', name: 'Yard Letters Discount ' . self::PRE_PACKED_DISCOUNT . '%', amount: $totalDiscountAmount);
            $this->updateTotalAmount($totalDiscountAmount);
        } else {
            $this->cart->removeAdditionalDiscountKey('prePackedDiscount');
        }
    }

    private function applySubTotalDiscount(): void
    {
        $discountAmount = 50;

        if ($this->cart->getSubTotal() >= 1000) {
            $this->cart->setAdditionalDiscountKey(key: "OFF50", amount: $discountAmount, name: "$50 OFF");
            $this->updateTotalAmount($discountAmount);
        } else {
            $this->cart->removeAdditionalDiscountKey('OFF50');
        }
    }

    public function applySub1500Discount(): void
    {
        $this->cart->removeAdditionalDiscountKey(self::SUB1500_CODE);

        $subTotal   = $this->cart->getSubTotal();
        $baseLimit  = self::SUB1500_BASE_LIMIT;
        $maxDiscount = self::SUB1500_MAX_DISCOUNT;

        if ($subTotal > $baseLimit) {

            $extraAmount    = $subTotal - $baseLimit;
            $discountAmount = min($extraAmount, $maxDiscount);

            $this->cart->setAdditionalDiscountKey(
                key: self::SUB1500_CODE,
                amount: $discountAmount,
                name: self::SUB1500_NAME
            );

            $this->updateTotalAmount($discountAmount);
        }
    }

    private function applyRewardDiscount(): void
    {
        $discountAmount = $this->rewardService->calculateCartDiscount(
            cart: $this->cart,
        );

        $isUserLoggedIn = $this->rewardService->isUserLoggedIn();

        if ($discountAmount > 0 && $isUserLoggedIn) {
            $this->cart->setAdditionalDiscountKey(key: 'rewardDiscount', name: 'YSP Rewards', amount: $discountAmount);
            $this->updateTotalAmount($discountAmount);
        } else {
            $this->cart->removeAdditionalDiscountKey('rewardDiscount');
        }
    }

    private function updateTotalAmount(float $discountAmount): void
    {
        $totalAmount = $this->cart->getTotalAmount() - $discountAmount;
        $this->cart->setTotalAmount($totalAmount);
    }

    private function isBiggerSize(string|array $size, string $refWidth = '24x36'): bool
    {
        list($refWidth, $refHeight) = explode('x', $refWidth);
        if (is_array($size)) {
            $width = $size['width'];
            $height = $size['height'];
        } else {
            list($width, $height) = explode('x', $size);
        }
        return ($width > $refWidth && $height > $refHeight) || ($width > $refHeight && $height > $refWidth);
    }

    private function recalculateNeedProof(Cart $cart): void
    {
        $cartNeedsProof = $this->appExtension->cartNeedsProof($cart);
        if(!$cart->isNeedProof() && $cartNeedsProof) {
            $this->cart->setDesignApproved(false);
            $cart->setNeedProof(true);
        }
    }

    private function calculateYSPLogoDiscount(float $price, float $unitAmount, int $quantity, array $YSPLogoDiscount) {
        $discountPercentage = $YSPLogoDiscount['hasLogo'] ? self::YSP_LOGO_DISCOUNT : 0;
        $discountAmountPerUnit = ($price * $discountPercentage) / 100;
        $totalDiscountAmountBeforeCap = round($discountAmountPerUnit * $quantity, 2);
        $totalDiscountAmount = min($totalDiscountAmountBeforeCap, self::YSP_MAX_DISCOUNT_AMOUNT);
        $discountedUnitAmount = round($unitAmount, 2) ;
        $totalAmount = round(($unitAmount * $quantity) - $totalDiscountAmount, 2);

        return [
            'discountAmount' => round($totalDiscountAmount, 2),
            'discountedUnitAmount' => $discountedUnitAmount,
            'totalAmount' => $totalAmount
        ];
    }

}