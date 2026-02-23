<?php

namespace App\Controller\Admin\Product;

use App\Entity\Category;
use App\Entity\ProductType;
use App\Enum\Admin\CacheEnum;
use App\Form\Admin\Product\ProductTypeCustomPricingType;
use App\Form\Admin\Product\ProductTypeFrameType;
use App\Form\Admin\Product\ProductTypePricingType;
use App\Form\Admin\Product\ProductTypeSEOMetaType;
use App\Form\Admin\Product\ProductTypeShippingType;
use App\Form\Admin\Product\ProductTypesType;
use App\Form\Admin\Product\ProductTypeVariantMetaData;
use App\Repository\ProductTypeRepository;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product-type')]
class ProductTypeController extends AbstractController
{
    #[Route('/', name: 'product_types')]
    public function index(Request $request, ProductTypeRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->get('page', 1);
        $productTypes = $paginator->paginate($repository->list(), $page, 10);
        return $this->render('admin/product/types/index.html.twig', [
            'productTypes' => $productTypes,
        ]);
    }

    #[Route('/add', name: 'product_type_add')]
    public function add(Request $request, SluggerInterface $slugger, ProductTypeRepository $repository): Response
    {
        $productType = new ProductType();
        $form = $this->createForm(ProductTypesType::class, $productType);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $productType->setSlug(strtolower($slugger->slug($productType->getName())));
            $repository->save($productType, true);
            $this->addFlash('success', 'Product Type has been created successfully.');
            return $this->redirectToRoute('admin_product_type_add');
        }
        return $this->render('admin/product/types/add.html.twig', [
            'form' => $form,
            'productType' => $productType,
        ]);
    }

    #[Route('/edit/{id}', name: 'product_type_edit')]
    public function edit(ProductType $productType, Request $request, SluggerInterface $slugger, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager, CacheService $cacheService): Response
    {
        $form = $this->createForm(ProductTypesType::class, $productType);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//            $csrfTokenManager->refreshToken('category_form');
            if ($productType->getSlug()) {
                $productType->setSlug(strtolower($slugger->slug($productType->getSlug())));
            } else {
                $productType->setSlug(strtolower($slugger->slug($productType->getName())));
            }
            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Product Type has been updated successfully.');
            return $this->redirectToRoute('admin_product_type_edit', ['id' => $productType->getId()]);
        }
        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form,
            'productType' => $productType,
        ]);
    }


    #[Route('/edit/{id}/seo-meta-data/{category}', name: 'product_type_seo_meta_data')]
    public function seoMetaData(
        ProductType $productType,
        #[MapEntity(mapping: ['category' => 'slug'])] Category $category,
        Request $request,
        ProductTypeRepository $repository,
        EntityManagerInterface $entityManager,
        CacheService $cacheService
    ): Response {
        $categories = $entityManager->getRepository(Category::class)->findBy(['store' => $productType->getStore(), 'slug' => 'custom-signs']);
        $form = $this->createForm(ProductTypeSEOMetaType::class, null, [
            'productType' => $productType,
            'category' => $category
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $metaData = $productType->getSeoMetaData();
            if (!isset($metaData[$category->getSlug()])) {
                $metaData[$category->getSlug()] = [];
            }
            $metaData[$category->getSlug()] = $data;
            $productType->setSeoMetaData($metaData);
            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Product Type SEO Meta Data has been updated successfully.');
            return $this->redirectToRoute('admin_product_type_seo_meta_data', ['id' => $productType->getId(), 'category' => $category->getSlug()]);
        }
        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form,
            'productType' => $productType,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    #[Route('/edit/{id}/pricing', name: 'product_type_pricing')]
    public function pricing(ProductType $productType, Request $request, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager, CacheService $cacheService): Response
    {
        $form = $this->createForm(ProductTypePricingType::class, null, [
            'productType' => $productType,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $variant => $pricing) {
                usort($pricing, fn($a, $b) => $a['qty'] <=> $b['qty']);
                $data[$variant] = $pricing;
            }
            $productType->setPricing($data);
            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Product Type Pricing has been updated successfully.');
            return $this->redirectToRoute('admin_product_type_pricing', ['id' => $productType->getId()]);
        }
        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form,
            'productType' => $productType,
        ]);
    }

    #[Route('/edit/{id}/pricing/{variant}/copy', name: 'product_type_pricing_copy')]
    public function pricingCopy(ProductType $productType, string $variant, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $pricing = $productType->getPricing();
        $hasHighData = array_search(max($pricing), $pricing);
        $highData = $pricing[$hasHighData];
        $pricing[$variant] = $highData;

        $productType->setPricing($pricing);
        $repository->save($productType, true);
        $this->addFlash('success', 'Product Type Pricing has been updated successfully.');
        return $this->redirectToRoute('admin_product_type_pricing', ['id' => $productType->getId()]);
    }

    #[Route('/edit/{id}/frame-pricing/{frameType}/delete', name: 'product_type_frame_pricing_delete')]
    public function framePricingDelete(ProductType $productType, string $frameType, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $framePricing = $productType->getFramePricing();
        unset($framePricing[$frameType]);
        $productType->setFramePricing($framePricing);
        $repository->save($productType, true);
        $this->addFlash('success', 'Product Type Frame Pricing has been updated successfully.');
        return $this->redirectToRoute('admin_product_type_frame', ['id' => $productType->getId()]);

    }

    #[Route('/edit/{id}/frame-pricing/{frameType}/copy', name: 'product_type_frame_pricing_copy')]
    public function framePricingCopy(ProductType $productType, string $frameType, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $framePricing = $productType->getFramePricing();
        $hasHighData = array_search(max($framePricing), $framePricing);

        $highData = $framePricing[$hasHighData];
        $framePricing[$frameType] = $highData;

        $productType->setFramePricing($framePricing);

        $repository->save($productType, true);
        $this->addFlash('success', 'Product Type Frame Pricing has been updated successfully.');
        return $this->redirectToRoute('admin_product_type_frame', ['id' => $productType->getId()]);
    }

    #[Route('/edit/{id}/shipping', name: 'product_type_shipping')]
    public function shipping(ProductType $productType, Request $request, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager, CacheService $cacheService): Response
    {
        $form = $this->createForm(ProductTypeShippingType::class, null, [
            'productType' => $productType,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('days')->getData();
            $shippingData = [];
            usort($data, fn($a, $b) => $a['day'] <=> $b['day']);
            foreach ($data as $day) {
                $shipping = [];
                usort($day['shipping'], fn($a, $b) => $a['qty'] <=> $b['qty']);
                foreach ($day['shipping'] as $chart) {
                    $shipping['qty_' . $chart['qty']] = $chart;
                }
                $shippingData['day_' . $day['day']] = [
                    'day' => $day['day'],
                    'shipping' => $shipping,
                ];
            }
            $productType->setShipping($shippingData);
            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Product Type shipping has been updated successfully.');
            return $this->redirectToRoute('admin_product_type_shipping', ['id' => $productType->getId()]);
        }
        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form,
            'productType' => $productType,
        ]);
    }

    #[Route('/edit/{id}/frame', name: 'product_type_frame')]
    public function frame(ProductType $productType, Request $request, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager, CacheService $cacheService): Response
    {
        $form = $this->createForm(ProductTypeFrameType::class, null, [
            'productType' => $productType,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            foreach ($data as $frameName => $framePricing) {
                usort($framePricing, fn($a, $b) => $a['qty'] <=> $b['qty']);
                $data[$frameName] = $framePricing;
            }
            $productType->setFramePricing($data);
            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Product Type Frame Price has been updated successfully.');
            return $this->redirectToRoute('admin_product_type_frame', ['id' => $productType->getId()]);
        }
        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form,
            'productType' => $productType,
        ]);
    }

    #[Route('/edit/{id}/shipping/{day}/copy', name: 'product_type_shipping_copy')]
    public function shippingCopy(ProductType $productType, string $day, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $shipping = $productType->getShipping();
        $shippingCounts = array_map(function ($dayObj) {
            return count($dayObj['shipping']);
        }, $shipping);
        $hasHighData = array_search(max($shippingCounts), $shippingCounts);
        $highData = $shipping[$hasHighData];
        $shipping['day_' . $day] = [
            'day' => $day,
            'shipping' => $highData['shipping'],
        ];

        $productType->setShipping($shipping);
        $repository->save($productType, true);
        $this->addFlash('success', 'Product Type shipping has been updated successfully.');
        return $this->redirectToRoute('admin_product_type_shipping', ['id' => $productType->getId()]);
    }

    #[Route('/edit/{id}/custom-pricing', name: 'product_type_custom_pricing')]
    public function customPricing(ProductType $productType, Request $request, ProductTypeRepository $repository, CsrfTokenManagerInterface $csrfTokenManager, CacheService $cacheService): Response
    {
        $form = $this->createForm(ProductTypeCustomPricingType::class, null, [
            'productType' => $productType,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $variant => $pricing) {
                usort($pricing, fn($a, $b) => $a['qty'] <=> $b['qty']);
                $data[$variant] = $pricing;
            }
            $productType->setCustomPricing($data);
            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Product Type Pricing has been updated successfully.');
            return $this->redirectToRoute('admin_product_type_custom_pricing', ['id' => $productType->getId()]);
        }
        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form,
            'productType' => $productType,
        ]);
    }

   #[Route('/edit/{id}/variant-meta', name: 'product_type_variant_meta_data')]
    public function variantMetaData(
        ProductType $productType,
        Request $request,
        ProductTypeRepository $repository,
        CacheService $cacheService
    ): Response {
        $form = $this->createForm(ProductTypeVariantMetaData::class, null, [
            'productType' => $productType,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $variantMeta = [];

            foreach ($productType->getDefaultVariants() as $variant) {
                $variantMeta[$variant] = [
                    'label' => $form->get("variant_label_$variant")->getData(),
                    'sort'  => $form->get("variant_sort_$variant")->getData(),
                ];
            }

            $productType->setVariantMetaData($variantMeta);

            $repository->save($productType, true);

            $cacheKey = $cacheService->getProductTypeCacheKey($productType->getSlug());
            $cacheService->clearCacheItem($cacheKey, CacheEnum::PRODUCT_TYPE->value);

            $this->addFlash('success', 'Variant meta data updated successfully.');

            return $this->redirectToRoute('admin_product_type_variant_meta_data', [
                'id' => $productType->getId()
            ]);
        }

        return $this->render('admin/product/types/edit.html.twig', [
            'form' => $form->createView(),
            'productType' => $productType,
        ]);
    }
}
