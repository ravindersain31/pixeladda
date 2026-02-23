<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\PaymentStatusEnum;
use App\Form\Admin\Order\ChargeItemType;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent(
  name: "ChargeItem",
  template: "admin/components/order/charge-item.html.twig"
)]
class ChargeItem extends AbstractController
{
  use DefaultActionTrait;
  use LiveCollectionTrait;
  use ComponentWithFormTrait;
  use ValidatableComponentTrait;
  use ComponentToolsTrait;


  #[LiveProp]
  public ?string $flashMessage = null;
  public ?string $flashError = 'success';
  public bool $isSuccessful = false;
  public bool $isNew = false;

  #[LiveProp]
  #[NotNull]
  public ?Order $order;

  public function __construct(private readonly EntityManagerInterface $entityManager, private readonly OrderService $orderService)
  {
  }

  protected function instantiateForm(): FormInterface
  {
    return $this->createForm(ChargeItemType::class, [], [
      'order' => $this->order
    ]);
  }
  public function hasValidationErrors(): bool
  {
    return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
  }

  #[LiveAction]
  public function save(): Response
  {
    $this->submitForm();
    $form = $this->getForm();
    $data = $form->getData();
    $this->validate();

    try {
      $order = $this->order;

      if($data['totalAmount'] <= 0){
        $this->addFlash('danger', 'Charge amount must be greater than zero');
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
      }


      $orderItem = new OrderItem();

      $orderItem->setQuantity(1);
      $orderItem->setPrice($data['totalAmount']);
      $orderItem->setUnitAmount($data['totalAmount']);
      $orderItem->setTotalAmount($data['totalAmount']);
      $orderItem->setItemType(OrderItem::ITEMSTATUS['CHARGED_ITEM']);
      $orderItem->setItemName($data['itemName']);
      $orderItem->setItemDescription($data['itemDescription']);

      $order->setTotalAmount($order->getTotalAmount() + $orderItem->getTotalAmount());
      $order->setSubTotalAmount($order->getSubTotalAmount() + $orderItem->getTotalAmount());

      $order->addOrderItem($orderItem);

      $order = $this->orderService->updatePaymentStatus($order);
      $this->entityManager->persist($order);
      $this->entityManager->flush();

      $this->addFlash('success', 'Order updated successfully!');
      return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);

    } catch (\Exception $e) {
      $this->addFlash('danger', $e->getMessage());
      return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
    }

  }

}