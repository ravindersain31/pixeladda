<?php

namespace App\Twig;

use App\Constant\HomePageBlocks;
use App\Constant\HomePageFooter;
use App\Entity\BulkOrder;
use App\Entity\Distributor;
use App\Entity\Category;
use App\Entity\ContactUs;
use App\Entity\Country;
use App\Entity\CustomerPhotos;
use App\Entity\Product;
use App\Entity\RequestCallBack;
use App\Entity\State;
use App\Entity\User;
use App\Enum\WholeSellerEnum;
use App\Helper\LightCartHelper;
use App\Helper\PriceChartHelper;
use App\Helper\ProductConfigHelper;
use App\Helper\PromoStoreHelper;
use App\Helper\ShippingChartHelper;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CartManagerService;
use App\Service\VirtualProductService;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigProvider extends AbstractController
{

    private array $store = [];


    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly HomePageBlocks $homePageBlocks,
        private readonly HomePageFooter $homePageFooter,
        private ProductConfigHelper $productConfigHelper,
        private CartManagerService $cartManagerService,
        private SerializerInterface $serializer,
        private readonly LightCartHelper $lightCartHelper,
        private readonly ShippingChartHelper $shippingChartHelper,
        private readonly PriceChartHelper $priceChartHelper,
        private readonly PromoStoreHelper $promoStoreHelper,
        private readonly VirtualProductService $virtualProductService,
        private readonly CategoryRepository $categoryRepository,

    ){
        $request = $requestStack->getCurrentRequest();
        if ($request) {
            $store = $request->get('store');
            if (is_array($store)) {
                $this->store = $request->get('store');
            }
        }
    }

    public function categories($store, $displayInMenu = false): array
    {
        return $this->entityManager->getRepository(Category::class)->getCategoryHasProductsSelective($store, $displayInMenu);
    }

    public function storeId()
    {
        return $this->store['id'] ?? null;
    }

    public function storeName()
    {
        return $this->store['name'] ?? null;
    }

    public function storeShortName()
    {
        return $this->store['shortName'] ?? null;
    }

    public function storeCurrency()
    {
        return $this->store['currencyCode'] ?? null;
    }

    public function storeCurrencySymbol()
    {
        return $this->store['currencySymbol'] ?? null;
    }

    public function productsBySku(string $skus = ''): array
    {
        return $this->entityManager->getRepository(Product::class)->findProductsBySku($skus);
    }

    public function getCustomerPhotos($store, bool $isRandom = false): array
    {
        return $this->entityManager->getRepository(CustomerPhotos::class)->getCustomerPhotos(store:$store, isRandom:  $isRandom, isEnabled: true);
    }

    public function getHomePageConstants($param): mixed
    {
        $isPromoStore = $this->promoStoreHelper->isPromoStoreByUrl($this->store['domain']);
        $constants = $this->homePageFooter->getConstants();

        return match (true) {
            $isPromoStore && $param === 'BANNERS' => $constants['PROMO_BANNERS'] ?? null,
            $isPromoStore && $param === 'INFO_EMAIL' => $constants['PROMO_INFO_EMAIL'] ?? null,
            default => $constants[$param] ?? null,
        };
    }

    public function getBanners(): array
    {
        return $this->homePageFooter->getActiveBanners();
    }

    public function getHomeIcons(): array
    {
        return $this->homePageFooter->getHomeIcons();
    }

    public function getSimilarProducts($limit = null): array
    {
        return $this->entityManager->getRepository(Product::class)->findProductsBySku(HomePageBlocks::getAllSkus(), $limit);
    }

    public function getDefaultCustomProduct(string $sku = 'CUSTOM'): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
    }

    public function isEnableProduct(string $sku = 'CUSTOM'): bool
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        return $product?->isIsEnabled() ?? false;
    }

    public function getEnv($param)
    {
        return $_ENV[$param] ?? null;
    }

    public function getCategoryBySlug($slug): Category|null
    {
        return $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);
    }

    public function getCountryByIsoCode($code): Country|null
    {
        return $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $code]);
    }

    public function getStateByIsoCode($code, Country|null|string $country): State|null
    {
        return $this->entityManager->getRepository(State::class)->findOneBy(['country' => $country, 'isoCode' => $code]);
    }

    public function getBulkOrderCount(): int
    {
        return $this->entityManager->getRepository(BulkOrder::class)->count(['status' => false]);
    }

    public function getDistributorCount(): int
    {
        return $this->entityManager->getRepository(Distributor::class)->count(['status' => 0, 'deletedAt' => null]);
    }


    public function getContactUsCount(): int
    {
        return $this->entityManager->getRepository(ContactUs::class)->count(['isOpened' => true]);
    }

    public function getWholeSellerAcceptedCount(): int
    {
        return $this->entityManager->getRepository(User::class)->count(['wholeSellerStatus' => WholeSellerEnum::ACCEPTED]);
    }

    public function getWholeSellerRejectedCount(): int
    {
        return $this->entityManager->getRepository(User::class)->count(['wholeSellerStatus' => WholeSellerEnum::REJECTED]);
    }

    public function getCallRequestsCount(): int
    {
        return $this->entityManager->getRepository(RequestCallBack::class)->count(['isOpened' => true]);
    }

    public function getCategoryHasProducts($store, $displayInMenu = false): array
    {
        return $this->entityManager->getRepository(Category::class)->getCategoryHasProducts($store, $displayInMenu);
    }

    public function getCategoryProducts($storeId, $category, ?array $notSkus = null, $limit = 10, ?string $variant = null): array
    {
        $queryBuilder = $this->entityManager->getRepository(Product::class)
            ->findByCategory($category, $storeId, $notSkus, $variant);

        if ($limit !== -1) {
            $queryBuilder->setMaxResults($limit);
        }

        if($category->getSlug() == 'custom-signs') {
            return $this->virtualProductService->makeVirtualProduct();
        } else {
            return $queryBuilder->getResult();
        }
    }

    public function getCategoryToProducts(
        $storeId,
        array $category,
        ?array $notSkus = null,
        $limit = 3,
        ?string $variant = null
    ): array {
        $categoryEntity = $this->categoryRepository->findBySlugAndStore($category['slug'],$storeId);

        if (!$categoryEntity) {
            return [];
        }

        if ($categoryEntity->getSlug() === 'custom-signs') {
            return $this->virtualProductService->makeVirtualProduct();
        }

        $qb = $this->entityManager
            ->getRepository(Product::class)
            ->findByCategory($categoryEntity, $storeId, $notSkus, $variant);

        if ($limit !== -1) {
            $qb->setMaxResults($limit);
        }

        return $qb->getResult();
    }

    public function getSearchConfig(?string $sku = 'CUSTOM-SIGN'): array
    {
        $product = self::getDefaultCustomProduct($sku);
        $framePricing = $product->getProductType()->getFramePricing();
        $buildframePricing = $this->priceChartHelper->buildFramePricing($framePricing);

        return [
            'product' => $this->productConfigHelper->getProductBasicInfo($product),
            'priceChart' => $this->productConfigHelper->getPriceChart($product),
            'variants' => $product->getVariants(),
            'framePricing' => $buildframePricing,
            'cart' => $this->cartManagerService->getCartSerialized($this->cartManagerService->getCart()),
        ];
    }

    public function getQuickQuoteConfig(?string $sku = 'CUSTOM-SIGN'): array
    {
        $product = self::getDefaultCustomProduct($sku);
        $shippings = $product->getProductType()->getShipping();
        $shippingChart = $this->shippingChartHelper->build($shippings);
        $framePricing = $product->getProductType()->getFramePricing();
        $buildframePricing = $this->priceChartHelper->buildFramePricing($framePricing);

        return [
            'product' => $this->productConfigHelper->getProductBasicInfo($product),
            'priceChart' => $this->productConfigHelper->getPriceChart($product),
            'variants' => $product->getVariants(),
            'shipping' => $shippingChart,
            'add_to_cart' => $this->generateUrl('add_to_cart', [], UrlGeneratorInterface::NETWORK_PATH),
            'framePricing' => $buildframePricing,
            'cart' => $this->cartManagerService->getCartSerialized($this->cartManagerService->getCart()),
        ];
    }

    public function getCartData(): array
    {
        return $this->lightCartHelper->build();
    }

    public function getNewArrivalsProducts(?int $limit = null): array
    {
        $qb = $this->entityManager->getRepository(Product::class)->getNewArrivalsQuery();

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getResult();
    }

    public function getNewArrivalCategories(
        ?string $storeId = null,
        ?int $limit = 10,
        ?array $chooseCategories = null
    ): array {
        $newCategories = $this->categoryRepository->getNewArrivalCategories($storeId);
        $olderCategories = $this->categoryRepository->getCategoryHasProductsSelective($storeId, false, false);

        $merged = array_merge($newCategories, $olderCategories);

        $uniqueCategories = [];
        foreach ($merged as $category) {
            $slug = $category['slug'] ?? null;
            if (!$slug) {
                continue;
            }

            if (!isset($uniqueCategories[$slug])) {
                $uniqueCategories[$slug] = $category;
            }
        }

        $categories = array_values($uniqueCategories);

        if (is_array($chooseCategories) && count($chooseCategories) > 0) {
            $categories = array_values(
                array_filter(
                    $categories,
                    fn ($category) => in_array($category['slug'], $chooseCategories, true)
                )
            );
        }

        return array_slice($categories, 0, $limit);
    }

}
