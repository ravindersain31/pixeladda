<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\ProductType;
use App\Form\Admin\Product\ProductTypesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminTypeProductForm",
    template: "admin/product/types/component/form.html.twig"
)]
class ProductTypeFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?ProductType $productType;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            ProductTypesType::class,
            $this->productType
        );
    }
}
