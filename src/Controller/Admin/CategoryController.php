<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Enum\Admin\CacheEnum;
use App\Form\Admin\Category\CategoryBlocksType;
use App\Form\Admin\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\CacheService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/categories')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'categories')]
    public function index(Request $request, CategoryRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->get('page', 1);
        $categories = $paginator->paginate($repository->list(), $page, 40);
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/add', name: 'category_add')]
    public function add(Request $request, SluggerInterface $slugger, CategoryRepository $repository, PaginatorInterface $paginator): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(strtolower($slugger->slug($category->getName())));
            $repository->save($category, true);
            $this->addFlash('success', 'Category has been created successfully.');
            return $this->redirectToRoute('admin_categories');
        }
        return $this->render('admin/category/add.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/edit/{id}', name: 'category_edit')]
    public function edit(Category $category, Request $request, SluggerInterface $slugger, CategoryRepository $repository, CsrfTokenManagerInterface $csrfTokenManager, CacheService $cacheService): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//            $csrfTokenManager->refreshToken('category_form');
            if ($category->getSlug()) {
                $category->setSlug(strtolower($slugger->slug($category->getSlug())));
            } else {
                $category->setSlug(strtolower($slugger->slug($category->getName())));
            }
            $repository->save($category, true);

            $prefix = $category->getParent() ? 'subcategory_slug' : 'category_slug';
            $cacheKey = $cacheService->getCategoryCacheKey($category->getSlug(), $prefix);
            $cacheService->clearCacheItem($cacheKey, CacheEnum::CATEGORY->value);

            $this->addFlash('success', 'Category has been updated successfully.');
            return $this->redirectToRoute('admin_categories');
        }
        return $this->render('admin/category/edit.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/sub-category/{id}', name: 'sub_categories')]
    public function subCategories(Category $category, Request $request, CategoryRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->get('page', 1);
        $categories = $paginator->paginate($category->getSubCategories(), $page, 40);
        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/category-blocks', name: 'category_blocks')]
    public function categoryBlocks(Request $request, Category $category, CategoryRepository $repository): Response
    {
        $form = $this->createForm(CategoryBlocksType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($category, true);
            $this->addFlash('success', 'Category Block has been updated successfully.');
            return $this->redirectToRoute('admin_category_blocks', ['id' => $category->getId()]);
        }

        return $this->render('admin/category/block.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }
}
