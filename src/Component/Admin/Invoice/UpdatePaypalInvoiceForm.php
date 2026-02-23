<?php

namespace App\Component\Admin\Invoice;

use App\Form\Admin\Invoice\PaypalInvoiceType;
use App\Service\Admin\PayPalInvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "UpdatePaypalInvoiceForm",
    template: "admin/components/update-paypal-invoice.html.twig"
)]
class UpdatePaypalInvoiceForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;

    #[LiveProp(fieldName: 'invoiceData')]
    public ?array $invoiceData = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PayPalInvoiceService $payPalInvoiceService
    ){
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(PaypalInvoiceType::class, ['invoiceData' => $this->invoiceData]);
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
        $this->isSuccessful = true;
        try {
            $validateInvoiceNumber = $this->payPalInvoiceService->checkIfInvoiceIsExist($data['invoiceNumber']);
            if($validateInvoiceNumber && $data['invoiceNumber'] != $this->invoiceData['detail']['invoice_number']){
                $this->flashError = 'danger';
                $this->flashMessage = 'This invoice Number is already exist.';
                return;
            }
            if(empty($data['items'])){
                $this->flashError = 'danger';
                $this->flashMessage = 'Please add items.';
                return;
            }
            $invoiceData = $this->payPalInvoiceService->createDraftInvoiceData($data);
            $this->payPalInvoiceService->updateInvoiceById($this->invoiceData['id'], $invoiceData);
            $this->addFlash('success', 'Invoice has been updated successfully');
            return $this->redirectToRoute('admin_invoice_paypal');

        } catch (\Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
    }
}