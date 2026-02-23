<?php

namespace App\Controller\Admin;

use App\Form\FilterTransactionType;
use App\Repository\OrderTransactionRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/transactions')]

class TransactionController extends AbstractController
{
    #[Route('/', name: 'transaction_list')]
    public function index(Request $request,OrderTransactionRepository $repository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));
        $filterForm = $this->createForm(FilterTransactionType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) { 
            $query = $repository->filtertransaction(
                    transactionId:  $filterForm->get('transactionId')->getData(),
                    paymentMethod:  $filterForm->get('paymentMethod')->getData(),
                    status: $filterForm->get('status')->getData(),
                );
        } else {
            $query = $repository->createQueryBuilder('t')->orderBy('t.id', 'DESC')->getQuery();
        }
        $page = $request->get('page', 1);
        $transactions = $paginator->paginate($query, $page, 32);
        return $this->render('admin/transaction/index.html.twig', [
            'transactions' => $transactions,
            'filterForm' => $filterForm,
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
        ]);
    }
}
