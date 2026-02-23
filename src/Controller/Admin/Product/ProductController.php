<?php

namespace App\Controller\Admin\Product;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Admin\Product\ProductFilterType;
use App\Form\Admin\Product\ProductImagesType;
use App\Form\Admin\Product\ProductType;
use App\Form\Admin\Product\ProductPricingType;
use App\Helper\SKUGenerator;
use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'products')]
    public function index(Request $request, ProductRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->query->getInt('page', 1);

        $filterForm = $this->createForm(ProductFilterType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);
        $categoryId = null;
        $category = $filterForm->get('category')->getData();
        if ($category instanceof Category) {
            $categoryId = $category->getId();
        }
        $searchTerm = $filterForm->get('search')->getData() ?? '';
        $query = $repository->searchProduct(
            query: $searchTerm,
            category: $categoryId,
            checkEnabled: false,
            result: false,
        );

        $products = $paginator->paginate($query, $page, 40);
        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/add', name: 'product_add')]
    public function add(Request $request, SluggerInterface $slugger, ProductRepository $repository, SKUGenerator $skuGenerator): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product->setSlug(strtolower($slugger->slug($product->getName())));
            $product->setSku($skuGenerator->generate($product));
            $product->setIsEnabled(false);
            $product->setisCustomSize(false);
            $repository->save($product, true);

            $this->addFlash('success', 'Product has been created successfully.');
            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }
        return $this->render('admin/product/add.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/edit/{id}', name: 'product_edit')]
    public function edit(Product $product, Request $request, SluggerInterface $slugger, ProductRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//            $csrfTokenManager->refreshToken('category_form');
            if ($product->getSlug()) {
                $product->setSlug(strtolower($slugger->slug($product->getSlug())));
            } else {
                $product->setSlug(strtolower($slugger->slug($product->getName())));
            }
            $repository->save($product, true);
            $this->addFlash('success', 'Product has been updated successfully.');
            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }
        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/edit/{id}/toggle-selling', name: 'product_edit_toggle_sell')]
    public function toggleSell(Product $product, ProductRepository $repository): Response
    {
        if ($product->isIsEnabled()) {
            $product->setIsEnabled(false);
            $repository->save($product, true);
            $this->addFlash('success', 'Product has been disabled successfully.');
            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        $isMissingData = false;
        $message = '';

        if (!$product->getImage()->getName()) {
            $isMissingData = true;
            $message = 'Product has missing product image. Please upload in overview tab.';
        } else {
            $variants = $product->getVariants();
            if ($variants->count() === 0) {
                $isMissingData = true;
                $message = 'Product has no variants. Please add in variants tab.';
            } else {
                foreach ($variants as $variant) {
                    if (!$variant->getImage()->getName()) {
                        $isMissingData = true;
                        $message = 'Product has missing template image for variant: ' . $variant->getName() . '. Please upload in variants tab.';
                        break;
                    }

                    $templateJson = $variant->getMetaDataKey('templateJson');
                    if ($templateJson === null) {
                        $isMissingData = true;
                        $message = 'Product has missing template design for variant: ' . $variant->getName() . '. Please upload in variants tab.';;
                        break;
                    }
                }
            }
        }

        if ($isMissingData) {
            $this->addFlash('danger', $message);
            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        $product->setIsEnabled(true);
        $repository->save($product, true);
        $this->addFlash('success', 'Product has been enabled successfully.');
        return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
    }

    #[Route('/edit/{id}/pricing', name: 'product_pricing')]
    public function pricing(Product $product, Request $request, ProductRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $form = $this->createForm(ProductPricingType::class, null, [
            'product' => $product,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $product->setPricing($data);
            $repository->save($product, true);
            $this->addFlash('success', 'Product Pricing has been updated successfully.');
            return $this->redirectToRoute('admin_product_pricing', ['id' => $product->getId()]);
        }
        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/edit/{id}/yard-letters', name: 'product_pre_packed')]
    public function prePackedImages(Product $product, Request $request, ProductRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $form = $this->createForm(ProductImagesType::class, $product, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $repository->save($product, true);
            $this->addFlash('success', 'Product Pricing has been updated successfully.');
            return $this->redirectToRoute('admin_product_pre_packed', ['id' => $product->getId()]);
        }
        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }
}
