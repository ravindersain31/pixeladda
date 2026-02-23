<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\Admin\Order\CommentItemType;
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
  name: "AddOrEditItemCommentForm",
  template: "admin/components/order/comment-item.html.twig"
)]
class AddOrEditItemCommentForm extends AbstractController
{
  use DefaultActionTrait;
  use LiveCollectionTrait;
  use ComponentWithFormTrait;
  use ValidatableComponentTrait;
  use ComponentToolsTrait;

  #[LiveProp]
  #[NotNull]
  public ?Order $order;

  #[LiveProp]
  public ?OrderItem $orderItem;

  public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly OrderService $orderService
  ){}

  protected function instantiateForm(): FormInterface
  {
    return $this->createForm(CommentItemType::class, $this->orderItem ?? [], [
      'order' => $this->order,
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

        if ($this->orderItem instanceof OrderItem) {
            $this->entityManager->persist($this->orderItem);
            $this->entityManager->flush();
            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $orderItem = new OrderItem();
        $orderItem->setQuantity(0);
        $orderItem->setPrice(0);
        $orderItem->setUnitAmount(0);
        $orderItem->setTotalAmount(0);
        $orderItem->setItemType(OrderItem::COMMENT_ITEM);
        $orderItem->setItemDescription($data['itemDescription']);
    
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