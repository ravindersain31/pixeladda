<?php

namespace App\Service;

use App\Constant\CustomSize;
use App\Helper\PriceChartHelper;
use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\User;

class VirtualProductService
{
    private User|null $user = null;
    public function __construct(
        private ProductRepository $productRepository,
        private ProductTypeRepository $productTypeRepository,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->user = $this->tokenStorage->getToken()?->getUser();
    }

    public function makeVirtualProduct(string $query = ''): array
    {
        $type = $this->productTypeRepository->findBySlug(slug: 'yard-sign');
        $seoMetaData = $type->getSeoMetaData();
        $customMockupSeo = $seoMetaData['custom-mockup'];

        $customProducts = $this->productRepository->findProductsForHomeBySkus(['CUSTOM/01', 'CUSTOM/02', 'CUSTOM/03', 'CUSTOM/04', 'CUSTOM/05', 'CUSTOM/06', 'CUSTOM/07', 'CUSTOM/08', 'CUSTOM/09', 'CUSTOM/10']);
        $customProduct = $this->productRepository->findProductsForHomeBySkus(['CUSTOM']);
        if (!empty($customProducts)) {
            $customProduct = reset($customProduct);
            $user = $this->user;
            foreach ($customProducts as &$cm) {
                $cm['categoryName'] = $customProduct['categoryName'];
                $cm['categorySlug'] = $customProduct['categorySlug'];
                $cm['productTypeName'] = $customProduct['productTypeName'];
                $cm['productTypeSlug'] = $customProduct['productTypeSlug'];
                $cm['productTypePricing'] = PriceChartHelper::getHostBasedPrice($customProduct['productTypePricing'], $type, $user);
                $cm['productPricing'] = $customProduct['productPricing'];
                $cm['productType'] = $type;
                $cm['lowestPrice'] = PriceChartHelper::getLowestPrice($cm['productTypePricing'], 'USD', $cm['slug']);
                $cm['highestPrice'] = PriceChartHelper::getHighestPrice($cm['productTypePricing'], 'USD', $cm['slug']);

                $metaData = $customMockupSeo['meta_data_' . $cm['name']];
                $cm['title'] = $metaData['title'];
                $cm['description'] = $metaData['description'];
                $cm['keywords'] = $metaData['keywords'];
            }
            $customSizeProduct = $this->productRepository->findProductsForHomeBySkus(['CUSTOM-SIZE']);
            $customSizeProduct = reset($customSizeProduct);
            $customSizeVariants = $this->productRepository->findProductsForHomeBySkus(['CUSTOM-SIZE/01']);
            $customSizes = CustomSize::SIZES;
            foreach ($customSizes as $size) {
                $customSizeVariant = reset($customSizeVariants);
                $customSizeVariant['name'] = $size;
                $customSizeVariant['slug'] = $size;
                $customSizeVariant['categoryName'] = $customSizeProduct['categoryName'];
                $customSizeVariant['categorySlug'] = $customSizeProduct['categorySlug'];
                $customSizeVariant['productTypeName'] = $customSizeProduct['productTypeName'];
                $customSizeVariant['productTypeSlug'] = $customSizeProduct['productTypeSlug'];
                $productTypePricing = array_merge($customProduct['productTypePricing'], $customSizeProduct['productTypeCustomPricing']);
                $customSizeVariant['productTypePricing'] = PriceChartHelper::getHostBasedPrice($productTypePricing, $type, $user);
                $customSizeVariant['productPricing'] = $customSizeProduct['productPricing'];
                $customSizeVariant['productType'] = $type;
                $customSizeVariant['imageName'] = 'CUSTOM/' . $size . '_v3.webp';
                $customSizeVariant['lowestPrice'] = PriceChartHelper::getLowestPrice($customSizeVariant['productTypePricing'], 'USD', $customSizeVariant['slug']);
                $customSizeVariant['highestPrice'] = PriceChartHelper::getHighestPrice($customSizeVariant['productTypePricing'], 'USD', $customSizeVariant['slug']);

                $metaData = $customMockupSeo['meta_data_' . $size];
                $customSizeVariant['title'] = $metaData['title'];
                $customSizeVariant['description'] = $metaData['description'];
                $customSizeVariant['keywords'] = $metaData['keywords'];

                $customProducts[] = $customSizeVariant;
            }
        }

        return $this->filterVirtualProductsByQuery($customProducts, $query);
    }

    public function filterVirtualProductsByQuery(array $products, string $query): array
    {
        if (!$query) {
            return $products;
        }

        $query = strtolower($query);
        return array_filter($products, function ($product) use ($query) {
            return (isset($product['name']) && str_contains(strtolower($product['name']), $query)) ||
                (isset($product['title']) && str_contains(strtolower($product['title']), $query)) ||
                (isset($product['keywords']) && str_contains(strtolower($product['keywords']), $query)) ||
                (isset($product['description']) && str_contains(strtolower($product['description']), $query));
        });
    }
}
