<?php

namespace App\Component\Admin\Invoice;

use App\Form\Admin\Invoice\StripeInvoiceType;
use App\Service\Admin\StripeInvoiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "StripeInvoiceForm",
    template: "admin/components/stripe-invoice.html.twig"
)]
class StripeInvoiceForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    public array $invoiceData = ['items' => []]; 

    public function __construct(
        private readonly StripeInvoiceService $stripeInvoiceService
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(StripeInvoiceType::class, ['invoiceData' => ['items' => []]]);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        try {
            if (empty($data['items'])) {
                $this->addFlash('danger', 'Please add at least one item.');
                return;
            }

            $response = $this->stripeInvoiceService->createInvoice($data);

            if (isset($response->status) && $response->status === 'error') {
                $this->addFlash('danger', $response->error ?? 'Unknown error');
                return;
            }

            $this->addFlash('success', 'Stripe Invoice created successfully.');
            return $this->redirectToRoute('admin_invoice_stripe');

        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }
    }
}
