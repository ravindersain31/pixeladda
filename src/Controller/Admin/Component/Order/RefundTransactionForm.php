<?php

namespace App\Controller\Admin\Component\Order;


use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Event\TransactionRefundEvent;
use App\Form\Admin\Order\RefundTransactionType;
use App\Payment\Refund;
use App\Service\CogsHandlerService;
use App\Service\OrderLogger;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
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
    name: "RefundTransactionForm",
    template: "admin/components/refund-transaction-form.html.twig"
)]
class RefundTransactionForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly OrderLogger              $orderLogger,
        private readonly MailerInterface          $mailer,
        private readonly Refund                   $refund,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CogsHandlerService       $cogs,
    )
    {
    }

    #[LiveProp]
    public ?Order $order = null;


    #[LiveProp]
    public ?OrderTransaction $transaction = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RefundTransactionType::class);
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $order = $this->transaction->getOrder();
        $refundedAmount = $this->transaction->getRefundedAmount();
        if ($refundedAmount >= $this->transaction->getAmount()) {
            $this->addFlash('info', 'This transaction was already refunded.');
        } else {
            $refundedBy = $this->getUser();
            $this->refund->setRefundedBy($refundedBy);
            $response = $this->refund->refund($this->transaction, $data);
            if ($response['success']) {
                $this->addFlash('success', $response['message']);
                $this->eventDispatcher->dispatch(new TransactionRefundEvent($order, $this->transaction, $response['refund']), TransactionRefundEvent::NAME);
                $this->cogs->syncRefundedAmount($order->getStore(), $order->getOrderAt());
            } else {
                $this->addFlash('danger', $response['message']);
            }
        }
        return $this->redirectToRoute('admin_order_transactions', ['orderId' => $order->getOrderId()]);
    }

}
