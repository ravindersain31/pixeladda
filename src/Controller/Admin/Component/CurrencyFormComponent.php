<?php

namespace App\Controller\Admin\Component;

use App\Entity\Currency;
use App\Form\Admin\Configuration\CurrencyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminCurrencyForm",
    template: "admin/components/currency-form.html.twig"
)]
class CurrencyFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Currency $currency;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            CurrencyType::class,
            $this->currency
        );
    }
}
