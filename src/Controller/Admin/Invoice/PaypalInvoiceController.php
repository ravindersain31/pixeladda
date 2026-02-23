<?php

namespace App\Controller\Admin\Invoice;

use App\Service\Admin\PayPalInvoiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/invoice')]
class PaypalInvoiceController extends AbstractController
{
    #[Route('/paypal', name: 'invoice_paypal')]
    public function index(Request $request, PayPalInvoiceService $payPalInvoiceService, ParameterBagInterface $parameterBag): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        try{

            $paypalInvoices = $payPalInvoiceService->getPayPalInvoices();
            $paypalUrl = $parameterBag->get('PAYPAL_BASE_URL');
            return $this->render('admin/invoice/index.html.twig',[
                'paypalInvoices' => $paypalInvoices['items'] ?? [],
                'paypalUrl' => $paypalUrl,
            ]);

        }catch(\Exception $e){
            $this->addFlash('danger',$e->getMessage());
            return $this->redirectToRoute('admin_dashboard');
        }
    }

    #[Route('/paypal/add', name: 'invoice_paypal_add')]
    public function add(Request $request): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        return $this->render('admin/invoice/_create_paypal_invoice.html.twig');
    }

    #[Route('/paypal/edit/{invoiceId}', name: 'invoice_paypal_edit')]
    public function edit(Request $request, PayPalInvoiceService $payPalInvoiceService): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $invoiceData = $payPalInvoiceService->getInvoiceById($request->get('invoiceId'));
        return $this->render('admin/invoice/_edit_paypal_invoice.html.twig',[
            'invoiceData' => $invoiceData
        ]);
    }

    #[Route('/paypal/refund/{invoiceId}', name: 'invoice_paypal_refund', methods: ['POST'])]
    public function refund(string $invoiceId, Request $request, PayPalInvoiceService $payPalInvoiceService): Response {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $amountInput = $request->request->get('refundAmount');
        $amount = $amountInput !== '' ? (float) $amountInput : null;

        try {
            $refundResponse = $payPalInvoiceService->refundInvoice($invoiceId, $amount);

            if ($refundResponse['success']) {
                $this->addFlash('success', $refundResponse['message']);
            } else {
                $this->addFlash('danger', $refundResponse['message']);
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_invoice_paypal');
    }

    #[Route('/paypal/send/{invoiceId}', name: 'invoice_paypal_send', methods: ['POST'])]
    public function sendInvoice(string $invoiceId, Request $request, PayPalInvoiceService $payPalInvoiceService): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        try {
            $response = $payPalInvoiceService->sendInvoiceById($invoiceId);

            if (isset($response['status']) && in_array($response['status'], [200, 201, 202], true)) {
                $this->addFlash('success', 'Invoice has been sent successfully.');
            } else {
                $message = $response['message'] ?? 'Failed to send invoice.';
                $this->addFlash('danger', $message);
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error while sending invoice: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_invoice_paypal');
    }
}
