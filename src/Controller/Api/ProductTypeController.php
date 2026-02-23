<?php

namespace App\Controller\Api;

use App\Helper\PriceChartHelper;
use App\Repository\ProductTypeRepository;
use App\Service\StoreInfoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductTypeController extends AbstractController
{
    #[Route('/pricing/product-type', name: 'fetch_pricing_product_type', methods: ['POST'])]
    public function fetchPricingByProductType(
        Request $request,
        ProductTypeRepository $productTypeRepository,
        PriceChartHelper $priceChartHelper,
        StoreInfoService $storeInfoService
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $slug = $data['slug'] ?? null;

            if (!$slug) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Product type slug is required'
                ], 400);
            }

            $productType = $productTypeRepository->findBySlug($slug);

            if (!$productType) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Product type not found'
                ], 404);
            }

            $pricing = $productType->getPricing();
            $pricing = $priceChartHelper->getHostBasedPrice($pricing, $productType);

            $framePricing = $productType->getFramePricing();
            $framePricing = $priceChartHelper->getHostBasedPrice($framePricing, $productType);

            $sortedPricing = $priceChartHelper->getSortedPricingBySlug($slug, $pricing);
            

            $html = $this->renderView('common/_pricing_section.html.twig', [
                'pricing' => $sortedPricing,
                'framePricing' => $framePricing,
                'productType' => $productType,
                'storeInfo' => $storeInfoService->storeInfo(),
            ]);

            $headingHtml = $this->renderView('common/_pricing_heading.html.twig', [
                'productType' => $productType,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'html' => $html,
                    'headingHtml' => $headingHtml,
                    'productType' => [
                        'id' => $productType->getId(),
                        'name' => $productType->getName(),
                        'slug' => $productType->getSlug()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred while fetching pricing data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
