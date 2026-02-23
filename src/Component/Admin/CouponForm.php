<?php

namespace App\Component\Admin;

use App\Entity\Admin\Coupon;
use App\Form\Admin\Coupon\CouponType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "CouponForm",
    template: "admin/coupon/component/form.html.twig"
)]
class CouponForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Coupon $coupon;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            CouponType::class,
            $this->coupon
        );
    }
}