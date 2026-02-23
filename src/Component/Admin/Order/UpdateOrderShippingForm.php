<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Entity\ProductType;
use App\Enum\OrderStatusEnum;
use App\Form\Admin\Order\OrderUpdateShippingType;
use App\Helper\ShippingChartHelper;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;

#[AsLiveComponent(
    name: "UpdateOrderShippingForm",
    template: "admin/components/order/update-order-shipping.html.twig"
)]
class UpdateOrderShippingForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository        $orderRepository,
        private readonly OrderService           $orderService,
        private readonly ShippingChartHelper    $shippingChartHelper,
    )
    {
    }

    #[LiveProp]
    #[NotNull]
    public ?Order $order;
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrderUpdateShippingType::class, $this->order);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    public function updateShipping()
    {
        $shippingDay = $this->form->get('shippingDate')->getData();

        $quantity = $this->order->getTotalQuantity();
        $productType = $this->entityManager->getRepository(ProductType::class)->findOneBy(['slug' => 'yard-sign']);
        $shippingChart = $this->shippingChartHelper->build($productType->getShipping());
        $shippingDates = $this->shippingChartHelper->getShippingByQuantity($quantity, $shippingChart);
        $customerShipping = $this->order->getShippingMetaDataKey('customerShipping') ?? [];

        $shipping = end($shippingDates);

        if(isset($shippingDates['day_'.$shippingDay])) {
            $shipping = $shippingDates['day_'.$shippingDay];
        }

        $this->order->setDeliveryDate(new \DateTimeImmutable($shipping['date']));
        $this->order->setShippingMetaDataKey('customerShipping', [
            ...$customerShipping,
            'day' => $shipping['day'],
            'date' => $shipping['date'],
        ]);
        $this->order->setMetaDataKey('isSaturdayDelivery', $shipping['isSaturday']);
    }

    #[LiveAction]
    public function save(): Response
    {

        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->validate();

        try {
            $this->updateShipping();
            $this->entityManager->persist($this->order);
            $this->entityManager->flush();
            $this->addFlash('success', 'Order Delivery Dates updated successfully');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
        }
    }

}