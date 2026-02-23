<?php

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Author;
use App\Form\Admin\Blog\AuthorType;
use App\Repository\Blog\AuthorRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class AuthorController extends AbstractController
{
    #[Route('/authors', name: 'blog_authors')]
    public function index(Request $request, PaginatorInterface $paginator, AuthorRepository $repository): Response
    {
        $page = $request->get('page', 1);
        $authors = $paginator->paginate($repository->list(), $page, 40);
        return $this->render('admin/blog/authors/index.html.twig', [
            'authors' => $authors,
        ]);
    }


    #[Route('/blog/author/add', name: 'blog_author_add')]
    public function add(Request $request, AuthorRepository $repository): Response
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($author, true);
            $this->addFlash('success', 'Author has been created successfully.');
            return $this->redirectToRoute('admin_blog_authors');
        }
        return $this->render('admin/blog/authors/add.html.twig', [
            'form' => $form->createView(),
            'author' => $author,
        ]);
    }

    #[Route('/author/edit/{id}', name: 'blog_author_edit')]
    public function edit(Author $author, Request $request, AuthorRepository $repository): Response
    {
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($author, true);
            $this->addFlash('success', 'Author has been updated successfully.');
            return $this->redirectToRoute('admin_blog_authors');
        }
        return $this->render('admin/blog/authors/edit.html.twig', [
            'form' => $form->createView(),
            'author' => $author,
        ]);
    }
}
