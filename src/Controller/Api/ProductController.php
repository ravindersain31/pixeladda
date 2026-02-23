<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Helper\PriceChartHelper;
use App\Helper\ProductConfigHelper;
use App\Repository\ProductRepository;
use App\Service\VirtualProductService;
use App\Trait\StoreTrait;
use App\Twig\ConfigProvider;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/product/list', name: 'list_products', methods: ['GET'])]
    public function productList(Request $request, ProductRepository $repository, SerializerInterface $serializer, PaginatorInterface $paginator): Response
    {
        $query = $request->get('q', '');
        $category = $request->get('c', null);
        $page = $request->get('p', 1);
        if($query){
            $productQuery = $repository->productBySearch(array_filter(explode(' ', $query)));
        }else{
            $productQuery = $repository->filterByCategoriesIds([$category]);
        }
        $products = $paginator->paginate($productQuery, $page, 32);

        return new Response($serializer->serialize($products, 'json', ['groups' => 'apiData']));
    }

    #[Route(path: '/product/{sku}', name: 'product_sku', defaults: ['sku' => 'CUSTOM-SIGN'], methods: ['GET'])]
    public function product(string $sku, ProductRepository $repository, SerializerInterface $serializer): Response
    {
        $product = $repository->findOneBy(['sku' => $sku]);
        if (!$product) {
            return new Response($serializer->serialize([], 'json', ['groups' => 'apiData']));
        }

        return new Response($serializer->serialize([$product], 'json', ['groups' => 'apiData']));
    }

    #[Route(path: '/product/config/{sku}', name: 'product_config', defaults: ['sku' => null], methods: ['GET'], requirements: ['sku' => '.+'])]
    public function productConfig(string $sku, Request $request, ProductRepository $repository, ProductConfigHelper $configHelper, SerializerInterface $serializer): Response
    {
        $product = $repository->findOneBy(['sku' => $sku]);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $productConfig = $configHelper->makeProductConfig($product, false);

        return $this->json([
            'category' => json_decode($serializer->serialize($product->getPrimaryCategory(), 'json', ['groups' => 'apiData'])),
            'productType' => json_decode($serializer->serialize($product->getProductType(), 'json', ['groups' => 'apiData'])),
            'product' => $productConfig,
        ]);
    }

    #[Route(path: '/shop/product/lists', name: 'shop_list_products', methods: ['GET'])]
    public function productsList(Request $request, ProductRepository $productRepository, VirtualProductService $virtualProductService, SerializerInterface $serializer, PaginatorInterface $paginator, PriceChartHelper $priceChartHelper): Response
    {

        $categories = array_filter(explode(',', $request->get('c', '')));
        $subCategories = array_filter(explode(',', $request->get('sc', '')));

        if ($request->get('search')) {
            $productQuery = $productRepository->productBySearch(array_filter(explode(' ', $request->get('search', ''))));
        } else {
            $productQuery = $productRepository->filterByCategories($categories, $subCategories);
        }
        $page = $request->get('page', 1);
        $products = $paginator->paginate($productQuery, $page, 32);
        $serializedProducts = json_decode($serializer->serialize($products, 'json', ['groups' => 'apiData']));

        $virtualProducts = [];
        if (in_array('custom-signs', $categories, true)) {
            $virtualProducts = $virtualProductService->makeVirtualProduct($request->get('search', ''));
        }

        return new JsonResponse([
            'products' => $serializedProducts,
            'virtualProducts' => $virtualProducts,
        ]);
    }

    #[Route(path: '/product/category/lists/{size?}', name: 'list_category_products', defaults: ['limit' => 9, 'size' => '24x18'], methods: ['GET'])]
    public function getFirstProductFromEachCategory(int $limit, string $size, EntityManagerInterface $entityManager, ConfigProvider $configProvider, SerializerInterface $serializer): Response
    {
        $categories = $configProvider->categories($this->store['id'], true);
        $result = ['categories' => []];

        foreach ($categories as $category) {
            $firstProduct = $entityManager->getRepository(Product::class)->findOneBy(['primaryCategory' => $category]);

            if ($firstProduct) {
                $categoryData = [
                    'category' => $category,
                    'variant' => null
                ];

                foreach ($firstProduct->getVariants() as $variant) {
                    if ($size === $variant->getName()) {
                        $categoryData['variant'] = $variant;
                        break;
                    }
                }

                $result['categories'][] = $categoryData;

                if (count($result['categories']) >= $limit) {
                    break;
                }
            }
        }

        return new Response($serializer->serialize($result, 'json', ['groups' => 'apiData']));
    }

    #[Route(path: '/search-config/{sku?}', name: 'search_config', defaults: ['sku' => 'CUSTOM-SIGN'], methods: ['GET'])]
    public function searchConfig(string $sku, ConfigProvider $configHelper, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($configHelper->getSearchConfig($sku), 'json', ['groups' => 'apiData']));
    }

    #[Route(path: '/quick-quote/{sku?}', name: '/quick_quote', defaults: ['sku' => 'CUSTOM-SIGN'], methods: ['GET'])]
    public function quickQuote(string $sku, ConfigProvider $configHelper, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($configHelper->getQuickQuoteConfig($sku), 'json', ['groups' => 'apiData']));
    }

    #[Route(path: '/product/price-chart/{productType?}', name: 'product_price_chart', defaults: ['productType' => 'yard-sign'], methods: ['GET'])]
    public function productPriceChart(string $productType, ProductConfigHelper $productConfigHelper, SerializerInterface $serializer, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(ProductType::class)->findOneBy(['name' => $productType]);
        if (!$product) {
            return new Response($serializer->serialize([], 'json', ['groups' => 'apiData']));
        }

        $price = $product->getPricing();

        return new Response($serializer->serialize($price, 'json', ['groups' => 'apiData']));
    }
}
