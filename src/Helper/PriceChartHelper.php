<?php

namespace App\Helper;

use App\Entity\ProductType;
use App\Enum\EspPercentageType;
use App\Enum\PromoStoreEnum;
use App\Enum\RolesEnum;
use App\Enum\WholeSellerEnum;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\User;

class PriceChartHelper
{
    private static ?Security $security = null;
    private static ?RequestStack $requestStack = null;
    private static ?ParameterBagInterface $parameterBag = null;

    public function __construct(Security $security, RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        self::$security = $security;
        self::$requestStack = $requestStack;
        self::$parameterBag = $parameterBag;
    }

    public static function getHost(): string
    {
        if (self::$requestStack?->getCurrentRequest()) {
            return self::$requestStack->getCurrentRequest()->getHost();
        }
    
        if (self::$parameterBag?->has('request.server.HTTP_HOST')) {
            return self::$parameterBag->get('request.server.HTTP_HOST');
        }
    
        return Request::createFromGlobals()->getHost();
    }

    public static function isPromoHost(): bool
    {
        $currentHost = self::getHost();
        return str_contains($currentHost, PromoStoreEnum::YARD_SIGN_PROMO->value);
    }

    public function build($pricing): array
    {
        $enhanced = [];
        $quantities = [];
        foreach ($pricing as $variantName => $variantPricing) {
            usort($variantPricing, fn($a, $b) => $a['qty'] <=> $b['qty']);
            $variantNameArr = explode('_', $variantName);
            $enhanced[$variantName] = [
                'label' => $variantNameArr[1] ?? $variantName,
                'pricing' => $this->getPriceChart($variantPricing, $quantities),
            ];
        }
        sort($quantities);
        return [
            'quantities' => $quantities,
            'variants' => $enhanced,
        ];
    }

    public function buildFramePricing($pricing): array
    {
        $quantities = [];
        $pricing = PriceChartHelper::getHostBasedFramePrice($pricing);
        $enhanced = [];
        foreach ($pricing as $frameName => $framePricing) {
            usort($framePricing, fn($a, $b) => $a['qty'] <=> $b['qty']);
            $variantName = str_replace('pricing_', '', $frameName);
            $enhanced[$frameName] = [
                'label' => $variantName,
                'pricing' => $this->getPriceChart($framePricing, $quantities),
            ];
        }
        sort($quantities);
        return [
            'quantities' => $quantities,
            'frames' => $enhanced,
        ];
    }

    public static function getHostBasedFramePrice(array $pricing, ?ProductType $productType = null,): array
    {
        $isPromoHost = self::isPromoHost();

        if (empty($pricing) || !self::isPromoHost()) {
            return $pricing;
        }

        $isLoggedIn = self::$security && self::$security->isGranted('IS_AUTHENTICATED_REMEMBERED');
       
        $user = self::$security?->getUser();
        if($user){
            $wholeSellerStatus = $user->getWholeSellerStatus()?->value;
        }
        if ($user && in_array(RolesEnum::WHOLE_SELLER->value, $user->getRoles(), true) && $wholeSellerStatus == "ACCEPTED") {
            $espType = $productType ? $productType->getAfterLoginEspType() : null;
            $espPercentage = $productType ? $productType->getAfterLoginEspPercentage() : null;
        } else {
            $espType = $productType ? $productType->getBeforeLoginEspType() : null;
            $espPercentage = $productType ? $productType->getBeforeLoginEspPercentage() : null;
        }
        
        if ($espType === null && $espPercentage === null) {
            return $pricing; 
        }

        foreach ($pricing as $frameKey => &$frameTiers) {
            foreach ($frameTiers as &$tier) {
                foreach ($tier as $key => &$value) {
                    if (in_array($key, ['usd', 'cad', 'eur']) && is_numeric($value)) {
                        $value = self::calculateHostBasedPrice((float)$value, $espType, $espPercentage, $isPromoHost);
                    }
                }
            }
        }
        return $pricing;
    }

    public function getPriceChart($pricing, &$quantities): array
    {
        $chart = [];
        foreach ($pricing as $key => $value) {
            $fromQty = intval($value['qty']);
            if (!in_array($fromQty, $quantities)) {
                $quantities[] = $fromQty;
            }
            $next = $pricing[$key + 1] ?? null;
            $chart['qty_' . $fromQty] = [
                'qty' => [
                    'from' => $fromQty,
                    'to' => $next ? intval($next['qty']) - 1 : 9999999999,
                ],
            ];
            unset($value['qty']);
            foreach ($value as $currency => $rate) {
                $chart['qty_' . $fromQty][$currency] = floatval($rate);
            }
        }
        return $chart;
    }

    public static function getLowestPrice(array $pricing, string $currency = 'USD', ?string $variant = null): float
    {
        if ($variant !== null) {
            $variants = self::getVariantsFromPricing($pricing);
            $variant = self::getClosestVariant($variant, $variants);
        }
        $lowest = 0;
        foreach ($pricing as $key => $chart) {
            if ($variant && $key !== 'pricing_' . $variant) {
                continue;
            }
            if (isset($chart['pricing'])) {
                $plainChart = [];
                foreach ($chart['pricing'] as $price) {
                    $plainChart[] = $price;
                }
                $chart = $plainChart;
            }
            foreach ($chart as $price) {
                if (is_array($price) && isset($price[strtolower($currency)])) {
                    if ($lowest === 0) {
                        $lowest = $price[strtolower($currency)];
                    } else {
                        if ($price[strtolower($currency)] < $lowest) {
                            $lowest = $price[strtolower($currency)];
                        }
                    }
                }
            }
        }
        return $lowest;
    }

    public static function getHighestPrice(array $pricing, string $currency = 'USD', ?string $variant = null): float
    {
        if ($variant !== null) {
            $variants = self::getVariantsFromPricing($pricing);
            $variant = self::getClosestVariant($variant, $variants);
        }

        $highest = 0;

        foreach ($pricing as $key => $chart) {
            if ($variant && $key !== 'pricing_' . $variant) {
                continue;
            }

            if (isset($chart['pricing'])) {
                $plainChart = [];
                foreach ($chart['pricing'] as $price) {
                    $plainChart[] = $price;
                }
                $chart = $plainChart;
            }

            foreach ($chart as $price) {
                if (is_array($price) && isset($price[strtolower($currency)])) {
                    $value = $price[strtolower($currency)];
                    if ($highest === 0 || $value > $highest) {
                        $highest = $value;
                    }
                }
            }
        }

        return $highest;
    }

    public static function getVariantsFromPricing(array $pricing): array
    {
        return array_map(function ($value) {
            return str_replace('pricing_', '', $value);
        }, array_keys($pricing));
    }

    public static function getClosestVariant(string $variant, array $variants): string
    {
        // Direct match
        if (in_array($variant, $variants) || count($variants) <= 0) {
            return $variant;
        }

        list($w, $h) = explode('x', $variant);
        $swappedVariantName = $h . 'x' . $w;
        // Swapped match
        if (in_array($swappedVariantName, $variants)) {
            return $swappedVariantName;
        }

        // Finding closest by area, ensuring the area is greater than or equal to the target
        $width = (int)$w;
        $height = (int)$h;
        $target = $width * $height;
        $closestSize = null;
        $closestDiff = PHP_INT_MAX;
        $largestVariant = null;
        $largestArea = 0;

        foreach ($variants as $v) {
            list($w, $h) = explode('x', $v);
            $sizeArea = (int)$w * (int)$h;

            // Track the largest variant
            if ($sizeArea > $largestArea) {
                $largestArea = $sizeArea;
                $largestVariant = $v;
            }

            // Only consider sizes with an area greater than or equal to the target
            if ($sizeArea >= $target) {
                $diff = abs($target - $sizeArea);

                if ($diff < $closestDiff) {
                    $closestDiff = $diff;
                    $closestSize = $v;
                }
            }
        }

        if ($closestSize === null) {
            return $largestVariant;
        }

        return $closestSize;
    }

    public static function getHostBasedPrice(array $pricing = [], ?ProductType $productType = null, User|null $user = null): array
    {
        $isPromoHost = self::isPromoHost(); 

        if (empty($pricing) || !self::isPromoHost()) {
            return $pricing;
        }

        $user = $user ?? self::$security?->getUser();
        if($user){
            $wholeSellerStatus = $user->getWholeSellerStatus()?->value;
        }

        if ($user && in_array(RolesEnum::WHOLE_SELLER->value, $user->getRoles(), true) && $wholeSellerStatus == "ACCEPTED") {
            $espType = $productType ? $productType->getAfterLoginEspType() : null;
            $espPercentage = $productType ? $productType->getAfterLoginEspPercentage() : null;
        } else {
            $espType = $productType ? $productType->getBeforeLoginEspType() : null;
            $espPercentage = $productType ? $productType->getBeforeLoginEspPercentage() : null;
        }

        if ($espType === null && $espPercentage === null) {
            return $pricing; 
        }

        foreach ($pricing as &$value) {            
            if (isset($value['pricing']) && is_array($value['pricing'])) {
                foreach ($value['pricing'] as &$qtyValue) {
                    if (isset($qtyValue['usd'])) {
                        $amount = (float) $qtyValue['usd'];
                        $qtyValue['usd'] = self::calculateHostBasedPrice($amount, $espType, $espPercentage, $isPromoHost);
                    }
                }
            } elseif (is_array($value)) {
                foreach ($value as &$qtyValue) {
                    if (isset($qtyValue['usd'])) {
                        $amount = (float) $qtyValue['usd'];
                        $qtyValue['usd'] = self::calculateHostBasedPrice($amount, $espType, $espPercentage, $isPromoHost);
                    }
                }
            }
        }

        return $pricing;
    }

    private static function calculateHostBasedPrice(float $originalPrice, string $espType, float $espPercentage, bool $isPromoHost): float 
    {
        $user = self::$security?->getUser();
        if ($isPromoHost) {
            if ($espType === EspPercentageType::ESP_PERCENTAGE_INCREMENT->value) {
                return round($originalPrice + ($originalPrice * $espPercentage / 100), 2);
            } else {
                if($espType === EspPercentageType::ESP_PERCENTAGE_DECREMENT->value) {
                    return round($originalPrice - ($originalPrice * $espPercentage / 100), 2);
                }
            }
        }

        return $originalPrice;
    }

    public static function getSortedPricingBySlug(string $slug, array $pricing): array
    {
        $sizeMap = [
            'yard-sign' => ["24x18","18x12","18x24","24x24","12x18","12x12","9x24","9x12","6x24", "6x18"],
            'hand-fans' => ["8x12","7x14","7x13","5x23","8x8","12x12","18x12","18x24","24x18", "24x24"],
            'big-head-cutouts' => ["24x18","18x12","18x24","24x24","12x18","12x12", "24x36","36x24","48x24", "48x48"],
            'die-cut' => ["24x18","18x12","18x24","24x24","12x18","12x12", "24x36","36x24","48x24", "48x48"],
            'blank-signs' => ["24x18","18x12","18x24","24x24","96x48"],
            'yard-letters' => ["24x18","18x12","18x24","24x24","12x18","12x12", "24x36","36x24","48x24", "48x48"],
        ];

        $defaultSizes = ["24x18","18x12","18x24","24x24","12x18","12x12","9x24","9x12","6x24", "6x18"];

        $selectedSizes = $sizeMap[$slug] ?? $defaultSizes;

        $sortedPricing = [];
        foreach ($selectedSizes as $size) {
            $key = 'pricing_' . $size;
            if (isset($pricing[$key])) {
                $sortedPricing[$key] = $pricing[$key];
            }
        }

        return $sortedPricing;
    }

}
