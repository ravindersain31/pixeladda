<?php

namespace App\Controller\Web\Component;

use App\Entity\Order;
use App\Helper\ShippingChartHelper;
use App\Service\OrderDeliveryDateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "DeliveryRotationForm",
    template: "components/order/_delivery_rotation_form.html.twig"
)]
class DeliveryRotationForm extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'order')]
    public ?Order $order;


    public function __construct(
        private readonly ShippingChartHelper $shippingChartHelper,
        private readonly OrderDeliveryDateService $orderDeliveryDateService    
    ){}

    public function newDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->orderDeliveryDateService->getNewShippingDate($this->order) ?? null;
    }
}
