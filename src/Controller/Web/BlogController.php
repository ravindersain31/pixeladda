<?php

namespace App\Controller\Web;

use App\Constant\MetaData\Page;
use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use App\Repository\Blog\CategoryRepository;
use App\Repository\Blog\PostRepository;
use App\Service\StoreInfoService;
use App\Trait\StoreTrait;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/blogs', name: 'blogs')]
    public function blogs(Request $request, PostRepository $postRepository, CategoryRepository $categoryRepository, PaginatorInterface $paginator, StoreInfoService $storeInfoService): Response
    {
        $page = $request->get('page', 1);
        $blogs = $paginator->paginate($postRepository->findbyStore($this->store['id']), $page, 10);

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('blog/index.html.twig', [
            'blogs' => $blogs,
            'categories' => $categoryRepository->findByStore($this->store['id'])->getResult(),
            'metaData' => $metaData,
        ]);
    }

    #[Route(path: '/blog/category/{slug}', name: 'blog_category')]
    public function category(#[MapEntity(mapping: ['slug' => 'slug'])] Category $category, Request $request, PaginatorInterface $paginator, PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $page = $request->get('page', 1);
        $blogs = $paginator->paginate($postRepository->findByCategory($category, $this->store['id']), $page, 10);
        return $this->render('blog/category.html.twig', [
            'blogs' => $blogs,
            'category' => $category,
            'categories' => $categoryRepository->findByStore($this->store['id'])->getResult(),
        ]);
    }

    #[Route(path: '/blog/{slug}', name: 'blog_post')]
    public function post(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $firstCategory = $post->getCategories()->first();
        $relatedPosts = $postRepository->findByCategoryForStore($post, $firstCategory, $this->store['id']);
        return $this->render('blog/post.html.twig', [
            'blog' => $post,
            'relatedPosts' => $relatedPosts,
            'categories' => $categoryRepository->findByStore($this->store['id'])->getResult(),
        ]);
    }

}