<?php

namespace App\Controller\Web\Component;

use App\Entity\Order;
use App\Form\ApproveProofType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "ApproveProofForm",
    template: "account/order/component/approve-proof-form.html.twig"
)]
class ApproveProofComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'order')]
    public ?Order $order;

    #[LiveProp(fieldName: 'data')]
    public ?array $data;

    #[LiveProp(fieldName: 'amazonPay')]
    public ?array $amazonPay = null;

    #[LiveProp(fieldName: 'savedCards')]
    public ?array $savedCards = [];

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ApproveProofType::class, $this->data);
    }

}
