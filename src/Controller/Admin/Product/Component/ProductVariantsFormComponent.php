<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\Product;
use App\Form\Admin\Product\ProductVariantsType;
use App\Form\Admin\Product\ProductVariantType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminProductVariantsForm",
    template: "admin/product/component/variants-form.html.twig"
)]
class ProductVariantsFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Product $product;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            ProductVariantsType::class,
            $this->product,
        );
    }
}
