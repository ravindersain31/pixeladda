<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\ProductType;
use App\Form\Admin\Product\ProductTypeShippingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminTypeProductShippingForm",
    template: "admin/product/types/component/shipping.html.twig"
)]
class ProductTypeShippingFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?ProductType $productType;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ProductTypeShippingType::class, null, [
            'productType' => $this->productType,
        ]);
    }
}
