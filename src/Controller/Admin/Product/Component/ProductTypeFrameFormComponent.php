<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\ProductType;
use App\Form\Admin\Product\ProductTypeFrameType;
use App\Form\Admin\Product\ProductTypePricingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminTypeProductFrameForm",
    template: "admin/product/types/component/frame.html.twig"
)]
class ProductTypeFrameFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?ProductType $productType;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ProductTypeFrameType::class, null, [
            'productType' => $this->productType,
        ]);
    }
}
