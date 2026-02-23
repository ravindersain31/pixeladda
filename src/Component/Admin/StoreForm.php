<?php

namespace App\Component\Admin;


use App\Entity\Store;
use App\Form\Admin\Configuration\StoreType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminStoreForm",
    template: "admin/components/store-form.html.twig"
)]
class StoreForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Store $store;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            StoreType::class,
            $this->store
        );
    }
}
