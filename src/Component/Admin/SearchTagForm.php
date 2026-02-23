<?php

namespace App\Component\Admin;

use App\Entity\Admin\Coupon;
use App\Entity\SearchTag;
use App\Form\Admin\Coupon\CouponType;
use App\Form\Admin\SearchTag\SearchTagType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "SearchTagForm",
    template: "admin/tags/component/form.html.twig"
)]
class SearchTagForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?SearchTag $searchTag;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            SearchTagType::class,
            $this->searchTag
        );
    }
}