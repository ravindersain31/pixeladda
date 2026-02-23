<?php

namespace App\Component\Admin;

use App\Entity\Fraud;
use App\Form\Admin\Fraud\FraudType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "FraudForm",
    template: "admin/fraud/component/form.html.twig"
)]
class FraudForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Fraud $fraud;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            FraudType::class,
            $this->fraud,
        );
    }
}