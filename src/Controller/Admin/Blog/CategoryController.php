<?php

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Category;
use App\Form\Admin\Blog\CategoryType;
use App\Repository\Blog\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blog')]
class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'blog_categories')]
    public function index(Request $request, PaginatorInterface $paginator, CategoryRepository $repository): Response
    {
        $page = $request->get('page', 1);
        $categories = $paginator->paginate($repository->list(), $page, 40);
        return $this->render('admin/blog/category/index.html.twig', [
            'categories' => $categories,
        ]);
    }


    #[Route('/category/add', name: 'blog_category_add')]
    public function add(Request $request, SluggerInterface $slugger, CategoryRepository $repository, PaginatorInterface $paginator): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(strtolower($slugger->slug($category->getTitle())));
            $repository->save($category, true);
            $this->addFlash('success', 'Category has been created successfully.');
            return $this->redirectToRoute('admin_blog_categories');
        }
        return $this->render('admin/blog/category/add.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/category/edit/{id}', name: 'blog_category_edit')]
    public function edit(Category $category, Request $request, SluggerInterface $slugger, CategoryRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($category->getSlug()) {
                $category->setSlug(strtolower($slugger->slug($category->getSlug())));
            } else {
                $category->setSlug(strtolower($slugger->slug($category->getTitle())));
            }
            $repository->save($category, true);
            $this->addFlash('success', 'Category has been updated successfully.');
            return $this->redirectToRoute('admin_blog_categories');
        }
        return $this->render('admin/blog/category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

}
