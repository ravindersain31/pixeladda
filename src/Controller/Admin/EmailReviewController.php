<?php

namespace App\Controller\Admin;

use App\Repository\EmailReviewRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/email-reviews')]
class EmailReviewController extends AbstractController
{
    #[Route('/', name: 'email_reviews')]
    public function index(Request $request, EmailReviewRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->get('page', 1);
        $emailReviews = $paginator->paginate($repository->list(), $page, 40);
        return $this->render('admin/email-review/index.html.twig', [
            'emailReviews' => $emailReviews,
        ]);
    }

}
