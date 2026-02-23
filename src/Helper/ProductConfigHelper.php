<?php

namespace App\Helper;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Service\StoreInfoService;
use App\Enum\ProductEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class ProductConfigHelper
{
    public function __construct(private readonly StoreInfoService $storeInfoService, private readonly EntityManagerInterface $entityManager, private readonly VichS3Helper $vichS3Helper, private readonly PriceChartHelper $priceChartHelper, private readonly ShippingChartHelper $shippingChartHelper)
    {
    }

    public function makeProductConfig(Product $product, array|bool $editData, bool $pricing = true): array
    {
        $basicInfo = $this->getProductBasicInfo($product);
        $variants = $this->getProductVariants($product, $editData);
        $customVariant = $this->getCustomProductVariant('CUSTOM-SIZE', $editData);
        $priceChart = $pricing ? $this->getPriceChart($product) : [];
        $shipping = $this->getShippingChart($product);
        $framePriceChart = $this->getFramePriceChart($product);
        $deliveryMethod = $this->getDeliveryMethods();
        return [
            ...$basicInfo,
            'variants' => $variants,
            'pricing' => $priceChart,
            'shipping' => $shipping,
            'framePricing' => $framePriceChart,
            'customVariant' => $customVariant,
            'deliveryMethods' => $deliveryMethod,
        ];
    }

    public function makeSampleProductConfig(Product $product, array|bool $editData): array
    {
        $basicInfo = $this->getProductBasicInfo($product);
        $variants = $this->getProductVariants($product, $editData);
        $customVariant = $this->getCustomProductVariant('CUSTOM-SIZE', $editData);
        $priceChart = $this->getProductPriceChart($product);
        $shipping = $this->getSampleShippingChart($product);

        $framePriceChart = $this->getFramePriceChart($product);
        $deliveryMethod = $this->getDeliveryMethods();
        return [
            ...$basicInfo,
            'variants' => $variants,
            'pricing' => $priceChart,
            'shipping' => $shipping,
            'framePricing' => $framePriceChart,
            'customVariant' => $customVariant,
            'deliveryMethods' => $deliveryMethod,
        ];
    }

    public function makeWireStakeConfig(Product $product, array|bool $editData): array
    {
        $basicInfo = $this->getProductBasicInfo($product);
        $variants = $this->getProductVariants($product, $editData);
        $priceChart = $this->getFramePriceChart($product);
        $shipping = $this->getShippingChart($product);
        $deliveryMethod = $this->getDeliveryMethods();


        return [
            ...$basicInfo,
            'variants' => $variants,
            'pricing' => $priceChart,
            'shipping' => $shipping,
            'deliveryMethods' => $deliveryMethod,
        ];
    }

    public function getProductBasicInfo(Product $product): array
    {
        $primaryCategory = $product->getPrimaryCategory();
        $productType = $product->getProductType();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'sku' => $product->getSku(),
            'isCustomizable' => $productType->isCustomizable() ?? false,
            'isCustom' => $product->getSku() === 'CUSTOM' || $product->getSku() === 'DC-CUSTOM' || $product->getSku() === 'BHC-CUSTOM' || $product->getSku() === 'HF-CUSTOM',
            'isYardLetters' => $productType->getSlug() === 'yard-letters',
            'isYardSign' => $productType->getSlug() === 'yard-sign',
            'isDieCut' => $productType->getSlug() === 'die-cut',
            'isWireStake' => $product->getSku() === 'WIRE-STAKE',
            'isSample' => $product->getSku() === 'SAMPLE',
            "isBlankSign" => $product->getSku() === 'BLANK-SIGN',
            'isBigHeadCutouts' => $productType->getSlug() === 'big-head-cutouts',
            'isHandFans' => $productType->getSlug() === 'hand-fans',
            'isSelling' => $product->isIsEnabled(),
            'productImages' => $this->getProductImages($product),
            'productType' => [
                'id' => $productType->getId(),
                'name' => $productType->getName(),
                'slug' => $productType->getSlug(),
                'allowCustomSize' => $productType->isAllowCustomSize(),
                'isCustomizable' => $productType->isCustomizable(),
                'quantityType' => in_array($productType->getSlug(), []) ? 'BY_QUANTITY' : 'BY_SIZES',
            ],
            'category' => [
                'id' => $primaryCategory->getId(),
                'name' => $primaryCategory->getName(),
                'slug' => $primaryCategory->getSlug(),
            ],
            'productMetaData' => [
                'totalSigns' => $product->getProductMetaDataKey('totalSigns'),
                'frameTypes' => $product->getProductMetaDataKey('frameTypes'),
            ]
        ];
    }

    public function getProductImages(Product $product): array
    {
        $images = $product->getProductImages()->toArray();

        $mappedImages = array_map(function ($image) {
            return $this->vichS3Helper->asset($image, 'imageFile') ?? null;
        }, $images);

        $mappedImages = array_merge([$this->vichS3Helper->asset($product, 'imageFile') ?? null], $mappedImages);
        $mappedImages = array_filter($mappedImages);

        return array_values($mappedImages);
    }

    public function getCustomProductVariant(string $sku, $editData): array
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $sku,'isCustomSize' => true]);
        if(!$product) return [];
        $variants = $this->entityManager->getRepository(Product::class)->findActiveVariants($product);
        $productType = $product->getProductType();
        return array_values((new ArrayCollection($variants))->map(function (Product $variant) use ($productType, $editData) {
            return $this->getProductVariant($variant, $productType, $editData);
        })->toArray());
    }

    public function getProductVariants(Product $product, array|bool $editData): array
    {
        $productType = $product->getProductType();
        $variants = $this->entityManager->getRepository(Product::class)->findActiveVariants($product);
        return array_values((new ArrayCollection($variants))->map(function (Product $variant) use ($productType, $editData) {
            return $this->getProductVariant($variant, $productType, $editData);
        })->toArray());
    }

    public function getProductVariant(Product $variant, ProductType $productType, array|bool $editData): array
    {
        $additionalData = $this->prepareAdditionalData($variant, $editData);
        $isCustom = str_contains($variant->getSku(), 'CUSTOM');
        $previewTypes = [
            'yard-letters' => 'image',
            'die-cut' => 'canvas',
            'big-head-cutouts' => 'canvas',
            'hand-fans' => 'canvas',
            'yard-sign' => 'canvas',
        ];

        $previewType = $previewTypes[$productType->getSlug()] ?? 'image';

        $isWireStake = false;
        $isSample = false;
        $isBlankSign = false;
        $isBHCCustom = false;
        $isHFCustom = false;
        $isDCCustom = false;

        if ($variant->getParent()->getSku() === ProductEnum::WIRE_STAKE->value) {
            $previewType = 'image';
            $isWireStake = true;
        }

        if ($variant->getParent()->getSku() === ProductEnum::SAMPLE->value) {
            $previewType = 'image';
            $isSample = true;
        }

        if ($variant->getParent()->getSku() === ProductEnum::BIG_HEAD_CUT_OUT->value) {
            $previewType = 'canvas';
            $isBHCCustom = true;
        }

        if ($variant->getParent()->getSku() === ProductEnum::BLANK_SIGN->value) {
            $previewType = 'image';
            $isBlankSign = true;
        }

        if ($variant->getParent()->getSku() === ProductEnum::HF_CUSTOM->value) {
            $previewType = 'canvas';
            $isHFCustom = true;
        }

        if ($variant->getParent()->getSku() === ProductEnum::DC_CUSTOM->value) {
            $previewType = 'canvas';
            $isDCCustom = true;
        }

        $varianttemplateFile = $this->storeInfoService->storeInfo()['isPromoStore'] ? $this->vichS3Helper->asset($variant, 'promoTemplateFile') : $this->vichS3Helper->asset($variant, 'templateFile');
        $variantImage = $this->vichS3Helper->asset($variant, 'imageFile');

        if ($isSample || $isWireStake || $isBHCCustom || $isHFCustom || $isDCCustom) {
            if ($this->storeInfoService->storeInfo()['isPromoStore']) {
                $variantImage = $this->vichS3Helper->asset($variant, 'promoImageFile');
            }
        }

        $variantNoneImage = $this->storeInfoService->storeInfo()['isPromoStore'] ? "https://static.yardsignplus.com/storage/editor/promo-none-1-6943f9516282b403767593.webp" : "https://static.yardsignplus.com/assets/grommets-none.png";
        
        return [
            'productId' => $variant->getId(),
            'name' => $variant->getName(),
            'label' => $variant->getLabel(),
            'sku' => $variant->getSku(),
            'isCustom' => $isCustom,
            'isWireStake' => $isWireStake,
            'isSample' => $isSample,
            'isBlankSign' => $isBlankSign,
            'isCustomSize' => $variant->isIsCustomSize(),
            'isSelling' => $variant->isIsEnabled(),
            'previewType' => $previewType,
            'image' => $variantImage ?? $variantNoneImage,
            'template' => $varianttemplateFile ?? 'https://yardsignplus-static.s3.amazonaws.com/product/template/no-template.json',
            ...$additionalData,
        ];
    }

    public function getProductPriceChart(Product $product): array
    {
        return $this->priceChartHelper->build($product->getPricing());
    }

    public function getPriceChart(Product $product): array
    {
        $productType = $product->getProductType();
        $pricing = $productType->getPricingAll();
        $pricing = $this->priceChartHelper->getHostBasedPrice($pricing, $productType);
        return $this->priceChartHelper->build($pricing);
    }

    public function getFramePriceChart(Product $product): array
    {
        $productType = $product->getProductType();
        $framePricing = $productType->getFramePricing();
        if($product->getSku() === 'WIRE-STAKE'){
            $framePricing = $this->priceChartHelper->getHostBasedPrice($framePricing, $productType);
        }
        return $this->priceChartHelper->buildFramePricing($framePricing);
    }

    public function getShippingChart(Product $product): array
    {
        $productType = $product->getProductType();
        $shipping = $productType->getShipping();
        $this->shippingChartHelper->setFreeShippingEnabled(true);
        if($product->getSku() === "WIRE-STAKE"){
            return $this->shippingChartHelper->build($shipping, enableDeliveryTiers: false);
        }
        return $this->shippingChartHelper->build($shipping);
    }

    private function getSampleShippingChart(Product $product): array
    {
        $this->shippingChartHelper->setFreeShippingEnabled(false);
        $this->shippingChartHelper->setEnableSorting(false);
        return $this->shippingChartHelper->build(OrderSampleHelper::$shippingConfig);
    }

    private function prepareAdditionalData(Product $variant, array|bool $editData): array
    {
        $data = [
            'id' => $variant->isIsCustomSize() ? $this->generateUniqueId() : $variant->getId(),
            'itemId' => null,
            'quantity' => 0,
        ];

        $isEdit = is_array($editData);
        $editItem = $editData['item'] ?? null;
        $editVariant = $editItem?->getProduct();
        if ($isEdit && $editVariant instanceof Product && $editVariant->getId() === $variant->getId()) {
            $data['id'] = $editItem->getData()['id'];
            $data['quantity'] = $editItem->getQuantity();
            $data['itemId'] = $editItem->getItemId();
        }
        $varianttemplateFile = $this->storeInfoService->storeInfo()['isPromoStore'] ? $this->vichS3Helper->asset($variant, 'promoTemplateFile') : $this->vichS3Helper->asset($variant, 'templateFile');

        $bucketBase = 'https://yardsignplus-static.s3.amazonaws.com/product/template/';
        $templateJson = $variant->getMetaDataKey('templateJson');
        if ($templateJson) {
            $templateUrl = $bucketBase . $templateJson;
            $data['template'] = $templateUrl;
        }
        if($variant->getParent()->getSku() === 'CUSTOM' || $variant->getParent()->getSku() === 'DC-CUSTOM' || $variant->getParent()->getSku() === 'BHC-CUSTOM' || $variant->getParent()->getSku() === 'HF-CUSTOM') {
            $data['customTemplate'] = $varianttemplateFile ?? 'https://static.yardsignplus.com/product/img/CUSTOM/24x18_6509e1e318c2c538866537.pdf';
            $data['customTemplateLabel'] = $variant->getTemplateLabel();
        }
        return $data;
    }

    public function getDeliveryMethods(): array
    {
        return [
            'DELIVERY' => [
                'key' => 'DELIVERY',
                'label' => 'Delivery',
                'type' => 'percentage',
                'discount' => 0,
            ],
            'REQUEST_PICKUP' => [
                'key' => 'REQUEST_PICKUP',
                'label' => 'Request Pickup',
                'type' => 'percentage',
                'discount' => 50,
            ],
        ];
    }

    public function generateUniqueId(): int {
        $timestamp = microtime(true) * 1000; // Get current time in milliseconds
        $randomPart = rand(0, 9999); // Generate a random number between 0 and 9999
        return (int) ($timestamp + $randomPart);
    }

}