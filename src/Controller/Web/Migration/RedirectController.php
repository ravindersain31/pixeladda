<?php

namespace App\Controller\Web\Migration;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/product/custom-signs/CM00001', name: 'custom_product_redirect')]
    public function customProductRedirect(Request $request, ProductRepository $productRepository): Response
    {
        $size = $request->get('size');
        $variant = $this->convertSize($size);

        $defaultValues = [
            'category' => 'custom-signs',
            'productType' => 'yard-sign',
            'sku' => 'CUSTOM',
        ];

        $product = $productRepository->findProduct('custom-signs', 'yard-sign', 'CUSTOM', 1);

        $productVariant = $productRepository->isVariantExists($product, $variant);

        $newUrl = $this->generateUrl('editor', array_merge(['variant' => $productVariant ? $variant : '6x18'], $defaultValues));

        return new RedirectResponse($newUrl, RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/product/{category}/{sku}', name: 'generic_product_redirect')]
    public function genericProductRedirect(Request $request): Response
    {
        $category = $request->attributes->get('category');
        $sku = $request->attributes->get('sku');

        $newUrl = $this->generateUrl('editor', ['category' => $category, 'productType' => 'yard-sign', 'sku' => $sku]);

        return new RedirectResponse($newUrl, RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }

    private function convertSize(?string $size): ?string
    {
        if ($size) {
            return str_replace('-', 'x', $size);
        }

        return '6x18';
    }

    #[Route('/cart/{slug}', name: 'generic_cart_redirect')]
    #[Route('/category/{slug}', name: 'generic_category_redirect')]
    #[Route('/proof/{slug}', name: 'generic_proof_redirect')]
    public function genericRedirectUrl(Request $request): Response
    {
        $entity = $request->attributes->get('_route');
        $value = $request->attributes->get('slug');
        switch ($entity) {
            case 'generic_cart_redirect':
                $newRoute = 'cart';
                $routeParams = ['cart' => $value];
                break;

            case 'generic_category_redirect':
                $newRoute = 'category';
                $routeParams = ['slug' => $value];
                break;
            case 'generic_proof_redirect':
                $newRoute = 'order_proof_redirect';
                $routeParams = ['oid' => $value];
                break;

            default:
                throw $this->createNotFoundException('Invalid entity');
        }
        $newUrl = $this->generateUrl($newRoute, $routeParams);
        return new RedirectResponse($newUrl, RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/pre-packed/shop/pre-packed/{sku}', name: 'prepacked_editor_redirect')]
    public function prepackedEditorRedirect(Request $request): Response
    {
        $sku = $request->attributes->get('sku');
        
        $newUrl = $this->generateUrl('editor', [
            'category'    => 'yard-letters',
            'productType' => 'yard-letters',
            'sku'         => $sku,
        ]);

        $queryString = $request->getQueryString();
        if ($queryString) {
            $newUrl .= '?' . $queryString;
        }

        return new RedirectResponse($newUrl, RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }
}