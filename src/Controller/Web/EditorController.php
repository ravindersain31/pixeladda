<?php

namespace App\Controller\Web;

use App\Constant\Faqs;
use App\Entity\ArtworkCategory;
use App\Entity\Category;
use App\Entity\CustomerPhotos;
use App\Entity\Product;
use App\Helper\PriceChartHelper;
use App\Helper\ProductConfigHelper;
use App\Helper\VichS3Helper;
use App\Repository\ProductRepository;
use App\Service\CartManagerService;
use App\Service\KlaviyoService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class EditorController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/shop/custom-{variant}-yard-sign', name: 'custom_yard_sign_editor', priority: 1)]
    public function customYardSign(Request $request): Response
    {
        $variant = $request->get('variant', '6x18');
        return $this->forward('App\Controller\Web\EditorController::editor', [
            'category' => 'custom-signs',
            'productType' => 'yard-sign',
            'sku' => 'CUSTOM',
            'variant' => $variant,
            'qty' => 1,
        ]);
    }

    #[Route(path: '/shop/custom-{variant}-die-cut', name: 'custom_die_cut_editor', priority: 1)]
    public function customDieCut(Request $request): Response
    {
        $variant = $request->get('variant', '24x24');
        return $this->forward('App\Controller\Web\EditorController::editor', [
            'category' => 'die-cut',
            'productType' => 'die-cut',
            'sku' => 'DC-CUSTOM',
            'variant' => $variant,
            'qty' => 1,
        ]);
    }

    #[Route(path: '/shop/custom-{variant}-big-head-cutouts', name: 'custom_big_head_cutouts_editor', priority: 1)]
    public function customBigHeadCutouts(Request $request): Response
    {
        $variant = $request->get('variant', '24x24');
        return $this->forward('App\Controller\Web\EditorController::editor', [
            'category' => 'big-head-cutouts',
            'productType' => 'big-head-cutouts',
            'sku' => 'BHC-CUSTOM',
            'variant' => $variant,
            'qty' => 1,
        ]);
    }

    #[Route(path: '/shop/custom-{variant}-hand-fans', name: 'custom_hand_fans_editor', priority: 1)]
    public function customHandFans(Request $request): Response
    {
        $variant = $request->get('variant', '24x24');
        return $this->forward('App\Controller\Web\EditorController::editor', [
            'category' => 'hand-fans',
            'productType' => 'hand-fans',
            'sku' => 'HF-CUSTOM',
            'variant' => $variant,
            'qty' => 1,
        ]);
    }

    #[Route(path: '/{category}/shop/{productType}/{sku}', name: 'editor', priority: -1)]
    public function editor(Request $request, ProductRepository $repository, ProductConfigHelper $configHelper, EntityManagerInterface $entityManager, SerializerInterface $serializer, CartManagerService $cartManagerService, VichS3Helper $vichS3Helper, Faqs $faqs, KlaviyoService $klaviyoService): Response
    {
        $user = $this->getUser();
        $store = $this->getStore();
        $storeId = $store->id ?? null;

        $cartShareId = null;
        $cart = null;
        if ($request->get('shareId')) {
            $cartShareId = $request->get('shareId');
            $cart = $cartManagerService->getShareCart($cartShareId);
        } else {
            $cartShareId = $request->get('cartId');
            $cart = $cartManagerService->getCart($cartShareId);
        }

        $itemId = $request->get('itemId');
        $categorySlug = $request->get('category');
        $productTypeSlug = $request->get('productType');
        $sku = $request->get('sku');
        $product = $repository->findProduct($categorySlug, $productTypeSlug, $sku, $storeId);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $variant = $request->get('variant', null);
        $productVariant = $repository->isVariantExists($product, $variant);
        if (!$productVariant) {
            $activeVariants = $repository->findActiveVariants($product);
            $productVariant = end($activeVariants);
            if (!$productVariant) {
                throw $this->createNotFoundException('Product variant not found');
            }
        }

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $productConfig = $configHelper->makeProductConfig($product, $editData);
        $wireStakeProduct = $repository->findOneBy(['store' => $store->id, 'sku' => 'WIRE-STAKE']);
        $wireStakeProductConfig = $wireStakeProduct ? $configHelper->makeWireStakeConfig($wireStakeProduct, false) : null;
        $cartData = null;
        if (is_array($editData)) {
            $itemId = $request->get('itemId');
            $cartData = $cartManagerService->getCartSerialized($editData['cart'], $itemId);
            $itemToEdit = reset($cartData['items']);
            if(is_array($itemToEdit) && isset($itemToEdit['data'])) {
                $variant = $itemToEdit['data']['name'] ?? $variant;
            }
        }

        $isDefaultVariantExists = array_filter($productConfig['variants'], function ($v) use ($variant) {
            return $v['name'] === $variant;
        });
        $fallbackVariant = $variant;
        if (count($isDefaultVariantExists) <= 0) {
            $fallbackVariant = $productConfig['variants'][0]['name'];
            if(!$variant) {
                $variant = $fallbackVariant;
            }
        }

        $artworkCategories = $entityManager->getRepository(ArtworkCategory::class)->findBy(['status' => true]);
        $categories = $entityManager->getRepository(Category::class)->getCategoryHasProducts($storeId);

        $cartOverview = $cartManagerService->getCartOverview($cart);

        $pricing = PriceChartHelper::getHostBasedPrice($productConfig['pricing']['variants'], $product->getProductType(), $user);   
        $lowestPrice = PriceChartHelper::getLowestPrice($pricing, 'USD', $variant);

        $productImage = $vichS3Helper->asset($product, 'imageFile');
        if (!$productImage) {
            $productVariant = $entityManager->getRepository(Product::class)->findOneBy(['name' => $fallbackVariant, 'parent' => $product]);
            $productImage = $vichS3Helper->asset($productVariant, 'displayImageFile');
            if (!$productImage) {
                $productImage = $vichS3Helper->asset($productVariant, 'imageFile');
            }
        }

        $productData = [
            'productId' => $product->getId() ?? '',
            'SKU' => $product->getSku() ?? '',
            'productName' => $product->getName() ?? '',
            'image' => $productImage ?? '',
            'category' => $categorySlug ?? '',
        ];

        $klaviyoService->viewedProduct($productData);

        return $this->render('editor/index.html.twig', [
            'variant' => $variant,
            'seoMeta' => $product->getSeoMeta(),
            'category' => $product->getPrimaryCategory(),
            'productType' => $product->getProductType(),
            'cart' => $cartData,
            'cartOverview' => $cartOverview,
            'store' => $store,
            'product' => $productConfig,
            'wireStakeProduct' => $wireStakeProductConfig,
            'lowestPrice' => $lowestPrice,
            'productImage' => $productImage,
            'links' => [
                'add_to_cart' => $this->generateUrl('add_to_cart', [], UrlGeneratorInterface::NETWORK_PATH),
                'repeat_order' => $this->generateUrl('editor_repeat_order', [], UrlGeneratorInterface::NETWORK_PATH),
                'list_artwork' => $this->generateUrl('list_artwork', [], UrlGeneratorInterface::NETWORK_PATH),
                'upload_artwork' => $this->generateUrl('upload_artwork', [], UrlGeneratorInterface::NETWORK_PATH),
                'upload_custom_design' => $this->generateUrl('upload_custom_design', [], UrlGeneratorInterface::NETWORK_PATH),
                'list_products' => $this->generateUrl('list_products', [], UrlGeneratorInterface::NETWORK_PATH),
                'product_sku' => $this->generateUrl('product_sku', [], UrlGeneratorInterface::NETWORK_PATH),
                'product_config' => $this->generateUrl('product_config', [], UrlGeneratorInterface::NETWORK_PATH),
                'share_canvas' => $this->generateUrl('share_canvas', [], UrlGeneratorInterface::NETWORK_PATH),
            ],
            'initialData' => [
                'variant' => $variant,
                'quantity' => intval($request->get('qty', 0)),
            ],
            'categories' => json_decode($serializer->serialize($categories, 'json', ['groups' => 'apiData'])),
            'artwork' => [
                'categories' => json_decode($serializer->serialize($artworkCategories, 'json', ['groups' => 'apiData']))
            ],
            'faqs' => $faqs->getFaqs(),
            'customerPhotos' => $entityManager->getRepository(CustomerPhotos::class)->findBy(['isEnabled' => true], ['id' => 'DESC'], 48)
        ]);
    }
}