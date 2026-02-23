<?php

namespace App\Component\Admin;

use App\Entity\Subscriber;
use App\Form\Admin\Customer\SubscriberType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "SubscriberForm",
    template: "admin/customer/subscriber/component/form.html.twig"
)]
class SubscriberForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Subscriber $subscriber;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            SubscriberType::class,
            $this->subscriber
        );
    }
}