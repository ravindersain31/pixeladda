<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\Admin\Order\DiscountItemType;
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
  name: "EditDiscountItemForm",
  template: "admin/components/order/edit-discount-item.html.twig"
)]
class EditDiscountItemForm extends AbstractController
{
  use DefaultActionTrait;
  use LiveCollectionTrait;
  use ComponentWithFormTrait;
  use ValidatableComponentTrait;
  use ComponentToolsTrait;

  public bool $isNew = false;

  #[LiveProp]
  #[NotNull]
  public ?Order $order;

  #[LiveProp(fieldName: 'formData')]
  public ?OrderItem $orderItem;

  public function __construct(private readonly EntityManagerInterface $entityManager, private readonly OrderService $orderService)
  {
  }

  protected function instantiateForm(): FormInterface
  {
    return $this->createForm(DiscountItemType::class, $this->orderItem, [
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
    $originalOrderItemData = clone $this->orderItem;

    $this->submitForm();
    $form = $this->getForm();
    $data = $form->getData();
    $this->validate();

    try {
      $order = $this->order;

      if ($data->getTotalAmount() <= 0) {
        $this->addFlash('danger', 'Charge amount must be greater than zero');
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
      }

      $orderItem = $this->orderItem;

      $order->setSubTotalAmount((($order->getSubTotalAmount() + $originalOrderItemData->getPrice()) - $data->getTotalAmount()));
      $order->setTotalAmount((($order->getTotalAmount() + $originalOrderItemData->getPrice()) - $data->getTotalAmount()));

      $orderItem->setQuantity(1);
      $orderItem->setPrice($data->getTotalAmount());
      $orderItem->setUnitAmount($data->getTotalAmount());
      $orderItem->setTotalAmount($data->getTotalAmount());
      $orderItem->setItemType(OrderItem::ITEMSTATUS['DISCOUNT_ITEM']);
      $orderItem->setItemName($data->getItemName());
      $orderItem->setItemDescription($data->getItemDescription());

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