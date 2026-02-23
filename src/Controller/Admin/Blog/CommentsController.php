<?php

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Comment;
use App\Repository\Blog\CommentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class CommentsController extends AbstractController
{
    #[Route('/comments', name: 'blog_comments')]
    public function index(Request $request, PaginatorInterface $paginator, CommentRepository $repository): Response
    {
        $page = $request->get('page', 1);
        $comments = $paginator->paginate($repository->list(), $page, 40);
        return $this->render('admin/blog/comments/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/comment/approve/{id}', name: 'blog_comment_approve')]
    public function approve(Comment $comment, CommentRepository $repository): Response
    {
        $comment->setApprovedAt(new \DateTimeImmutable());
        $repository->save($comment, true);
        $this->addFlash('success', 'Comment approved successfully');
        return $this->redirectToRoute('admin_blog_comments');
    }

    #[Route('/comment/delete/{id}', name: 'blog_comment_delete')]
    public function delete(Comment $comment, CommentRepository $repository): Response
    {
        $comment->setDeletedAt(new \DateTimeImmutable());
        $repository->save($comment, true);
        $this->addFlash('success', 'Comment deleted successfully');
        return $this->redirectToRoute('admin_blog_comments');
    }

}
