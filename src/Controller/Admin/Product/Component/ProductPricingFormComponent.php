<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\Product;
use App\Form\Admin\Product\ProductPricingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminProductPricingForm",
    template: "admin/product/component/pricing.html.twig"
)]
class ProductPricingFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Product $product;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ProductPricingType::class, null, [
            'product' => $this->product,
        ]);
    }
}
