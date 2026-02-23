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
    name: "PaypalInvoiceForm",
    template: "admin/components/paypal-invoice.html.twig"
)]
class PaypalInvoiceForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PayPalInvoiceService $payPalInvoiceService
    ){
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(PaypalInvoiceType::class, ['invoiceData' => ['items' => []]]);
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
            if($validateInvoiceNumber){
                $this->flashError = 'danger';
                $this->flashMessage = 'This invoice Number is already exist.';
                return;
            }
            if(empty($data['items'])){
                $this->flashError = 'danger';
                $this->flashMessage = 'Please add items.';
                return;
            }
            $draftArray = $this->payPalInvoiceService->createDraftInvoiceData($data);
            $response = $this->payPalInvoiceService->generateInvoice($draftArray);
            if($response['status'] == 201 || $response['status'] == 200 || $response['status'] == 202){
                $this->addFlash('success', 'Invoice has been created successfully');
                return $this->redirectToRoute('admin_invoice_paypal');
            }else{
                $this->flashError = 'danger';
                $this->flashMessage = $response['error'];
            }
        } catch (\Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
    }
}