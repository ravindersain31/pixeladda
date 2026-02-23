<?php

namespace App\Controller\Admin\Component\Order;


use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderCancelledEvent;
use App\Form\Admin\Order\CancelOrderType;
use App\Payment\Refund;
use App\Service\CogsHandlerService;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "CancelOrderForm",
    template: "admin/components/cancel-order-form.html.twig"
)]
class CancelOrderForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly OrderLogger              $orderLogger,
        private readonly MailerInterface          $mailer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Refund                   $refund,
        private readonly CogsHandlerService       $cogs,
    )
    {
    }

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    #[LiveProp]
    public ?Order $order = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CancelOrderType::class);
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($this->order->getStatus() === OrderStatusEnum::CANCELLED) {
            $this->cogs->syncCancelledOrders($this->order->getStore(), $this->order->getOrderAt());
            $this->addFlash('warning', 'This order was already cancelled.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
        }

        $currentUser = $this->getUser();
        $isProcessRefund = $form->get('refund')->getData() === 'YES';

        $remarks = $form->get('cancellationNotes')->getData();

        $this->orderLogger->setOrder($this->order);
        $message = 'This order has been cancelled';
        if ($remarks) {
            $message .= ' with remarks "' . nl2br($remarks) . '"';
        }
        $this->order->setStatus(OrderStatusEnum::CANCELLED);
        $this->order->setCancelledBy($currentUser);
        $this->order->setMetaDataKey('cancellationNotes', nl2br($remarks ?? ''));
        $partialRefund = false;
        if ($isProcessRefund) {
            $this->order->setPaymentStatus(PaymentStatusEnum::REFUNDED);
            $refundType = $form->get('refundType')->getData();
            $message .= $refundType === 'PARTIAL_REFUND' ? ' and partially refunded.' : ' and processed the refund for all associated payments.';
            $amount = $form->has('amount') ? $form->get('amount')->getData() : 0;

            foreach ($this->order->getTransactions() as $transaction) {
                if (in_array($transaction->getStatus(), [PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::COMPLETED])) {
                    if($refundType === 'PARTIAL_REFUND') {
                        $refundedAmount = $transaction->getRefundedAmount();
                        $remainingAmount = $transaction->getAmount() - $refundedAmount;
                        if (($refundedAmount >= $transaction->getAmount()) || ($amount > $remainingAmount)) {
                            continue;
                        }
                    }
                    $this->refund->setRefundedBy($currentUser);
                    $refundResponse = $this->refund->refund($transaction, [
                        'amount' => $amount,
                        'refundType' => $refundType,
                        'internalNote' => 'Order cancelled',
                        'customerNote' => 'Order cancelled',
                    ]);
                    if($refundType === 'PARTIAL_REFUND' && $refundResponse['success']) {
                        $partialRefund = true;
                        break;
                    }
                    if (!$refundResponse['success']) {
                        $this->orderLogger->log('Not able to refund #' . $transaction->getTransactionId() . ' while cancelling the order due to "' . $refundResponse['message'] . '"');
                    }
                } else {
                    if ($transaction->getStatus() !== PaymentStatusEnum::REFUNDED) {
                        $transaction->setStatus(PaymentStatusEnum::CANCELLED);
                        $this->entityManager->persist($transaction);
                        $this->entityManager->flush();
                    }
                }
            }
        }
        if(!$partialRefund && $isProcessRefund && $refundType === 'PARTIAL_REFUND') {
            $this->flashError = 'danger';
            $this->flashMessage = 'Not able to refund as the refund amount is greater than the transaction amount';
            return $form->createView();
        }
        $this->orderLogger->log($message);

        $this->entityManager->persist($this->order);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new OrderCancelledEvent($this->order, $isProcessRefund), OrderCancelledEvent::NAME);
        $this->cogs->syncRefundedAmount($this->order->getStore(), $this->order->getOrderAt());
        $this->cogs->syncCancelledOrders($this->order->getStore(), $this->order->getOrderAt());
        $this->addFlash('success', $message);
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
    }

}
