<?php

namespace App\Controller\Admin\Product;

use App\Entity\Product;
use App\Form\Admin\Product\ProductVariantsType;
use App\Helper\SKUGenerator;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
class ProductVariantController extends AbstractController
{

    #[Route('/{productId}/variants', name: 'product_variants')]
    public function variants(string $productId, Request $request, ProductRepository $repository, SluggerInterface $slugger, SKUGenerator $skuGenerator): Response
    {
        $product = $repository->find($productId);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $form = $this->createForm(ProductVariantsType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $generatedSkusInRequest = [];
            $variants = $product->getVariants()?->toArray() ?? [];
            $variantDefaultImage = null;

            $productType = $product->getProductType();

            if ($productType->getSlug() === "yard-letters") {

                foreach ($variants as $variant) {
                    if ($variant->getImage()) {
                        $variantDefaultImage = $variant->getImage();
                    }
                }
            }

            foreach ($product->getVariants() as $variant) {
                if ($variant->getId() === null) {
                    $variant->setSlug(strtolower($slugger->slug($variant->getName())));
                    $newSku = $skuGenerator->generateVariant($product, $generatedSkusInRequest);
                    $variant->setSku($newSku);
                    $generatedSkusInRequest[] = $newSku;
                    $variant->setIsEnabled(true);
                    if ($variantDefaultImage && ($productType->getSlug() === "yard-letters")) {
                        $variant->setImage($variantDefaultImage);
                    }
                }
            }
            $repository->save($product, true);
            $this->addFlash('success', 'Product variants have been saved successfully.');
            return $this->redirectToRoute('admin_product_variants', ['productId' => $product->getId()]);
        }
        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{productId}/variant/init', name: 'product_variant_init')]
    public function init(int $productId, SluggerInterface $slugger, ProductRepository $repository, SKUGenerator $skuGenerator): Response
    {
        $product = $repository->find($productId);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        if ($product->getVariants()->count() > 0) {
            $this->addFlash('danger', 'Product already has variants.');
            return $this->redirectToRoute('admin_product_variants', ['productId' => $product->getId()]);
        }

        $productType = $product->getProductType();
        $variants = $productType->getDefaultVariants();
        $variantMeta = $productType->getVariantMetaData() ?? [];

        foreach ($variants as $index => $variantName) {
            $meta = $variantMeta[$variantName] ?? ['label' => '', 'sort' => $index + 1];

            $variant = new Product();
            $variant->setParent($product);
            $variant->setName($variantName);
            $variant->setLabel($meta['label']);
            $variant->setSlug(strtolower($slugger->slug($variantName)));
            $variant->setSku($skuGenerator->generateVariant($product));
            $variant->setIsEnabled(true);
            $variant->setSortPosition($meta['sort']);
            $repository->save($variant, true);
        }

        $this->addFlash('success', 'Product variants have been created successfully.');
        return $this->redirectToRoute('admin_product_variants', ['productId' => $product->getId()]);
    }

}
