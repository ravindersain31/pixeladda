<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\Admin\Product\ProductImagesType;
use App\Form\RequestChangesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "ProductPrePackedFrom",
    template: "account/order/component/product-files-form.html.twig"
)]
class ProductPrePackedFrom extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'product')]
    public ?Product $product;

    public function getUploadingImagesCount()
    {
        return $this->product->getProductImages()->filter(fn(ProductImage $image) => $image->getId() === null)->count();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ProductImagesType::class, $this->product);
    }


}
