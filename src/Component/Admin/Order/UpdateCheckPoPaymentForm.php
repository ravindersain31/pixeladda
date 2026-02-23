<?php

namespace App\Component\Admin\Order;

use App\Form\Admin\Order\UpdateCheckPoPaymentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
  name: "UpdateCheckPoPaymentForm",
  template: "admin/components/order/update-payment.html.twig"
)]
class UpdateCheckPoPaymentForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

  protected function instantiateForm(): FormInterface
  {
    return $this->createForm(
      UpdateCheckPoPaymentType::class,
    );
  }
}