<?php

namespace App\Controller\Admin\Invoice;

use App\Service\Admin\StripeInvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoice/stripe')]
class StripeInvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StripeInvoiceService $stripeInvoiceService
    ) {}

    #[Route('/', name: 'invoice_stripe')]
    public function index(Request $request,  PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        try {
            $allInvoices = $this->stripeInvoiceService->getAllInvoices(); 

            $page = $request->query->getInt('page', 1); 
            $limit = 20; 

            $stripeInvoices = $paginator->paginate(
                $allInvoices, 
                $page,
                $limit
            );

            return $this->render('admin/invoice/stripe/index.html.twig', [
                'stripeInvoices' => $stripeInvoices
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Unexpected error: ' . $e->getMessage());
            return $this->redirectToRoute('admin_dashboard');
        }
    }

    #[Route('/create', name: 'invoice_stripe_create')]
    public function create(Request $request): Response {
        $this->denyAccessUnlessGranted($request->get('_route'));

        return $this->render('admin/invoice/stripe/create.html.twig');
    }

    #[Route('/refund/{invoiceId}', name: 'invoice_stripe_refund', methods: ['POST'])]
    public function refund(string $invoiceId, Request $request): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $amountInput = $request->request->get('refundAmount');
        $amount = $amountInput !== null && $amountInput !== '' ? (float)$amountInput : null;

        try {
            $response = $this->stripeInvoiceService->refundInvoice($invoiceId, $amount);

            if ($response->status === 'error') {
                $this->addFlash('danger', $response->error);
            } else {
                $msg = sprintf(
                    'Refund successful! Refund ID: %s, Amount: $%s',
                    $response->refundId,
                    number_format($response->amount, 2)
                );
                $this->addFlash('success', $msg);
            }

            return $this->redirectToRoute('admin_invoice_stripe');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Error processing refund: ' . $e->getMessage());
            return $this->redirectToRoute('admin_invoice_stripe');
        }
    }
}
