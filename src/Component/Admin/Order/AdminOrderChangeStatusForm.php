<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderMessage;
use App\Enum\OrderStatusEnum;
use App\Event\OrderChangesRequestedEvent;
use App\Form\Admin\Order\AdminOrderChangeStatusType;
use App\Helper\VichS3Helper;
use App\Repository\OrderRepository;
use App\Service\OrderLogger;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "AdminOrderChangeStatusForm",
    template: "admin/order/view/action/component/_change_order_status.html.twig"
)]
class AdminOrderChangeStatusForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?Order $order = null;

    public function __construct(
        private readonly EntityManagerInterface         $entityManager,
        private readonly OrderLogger                    $orderLogger,
        private readonly OrderRepository                $repository,
        private readonly OrderService                   $orderService,
        private readonly VichS3Helper                   $s3Helper,
        private readonly EventDispatcherInterface       $eventDispatcher
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(AdminOrderChangeStatusType::class);
    }

    #[LiveAction]
    public function changeStatus()
    {
        $this->validate();
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $oldOrder =  clone $this->order;

        $order = $this->order;
        if (empty($order)) {
            $this->addFlash('danger', 'Order not found.');
            return;
        }

        $order->setStatus(OrderStatusEnum::CHANGES_REQUESTED);
        $order->setProofApprovedAt(null);
        $order->setApprovedProof(null);
        $order->setIsApproved(false);
        $order->setNeedProof(true);

        $user = $this->getUser();
        $csrName = $user ? $user->getUsername() : 'Unknown CSR';

        $timestamp = new \DateTime();

        $reason = $form->get('comments')->getData();

        $content = sprintf(
            "Order status changed by CSR: %s\nTimestamp: %s<strong>Comments</strong>: %s\n",
            $csrName . '<br/>',
            $timestamp->format('Y-m-d H:i:s') . '<br/>',
            $reason . '<br/>',
        );

        if ($oldOrder->getApprovedProof() && $oldOrder->getApprovedProof()->getFiles()) {
            $content .= "<b>Proof Links:</b> ";
            foreach ($oldOrder->getApprovedProof()->getFiles() as $uploadedFile) {
                $fileUrl = $this->s3Helper->asset($uploadedFile, 'fileObject');
                if ($uploadedFile->getType() == 'PROOF_IMAGE') {
                    $content .= '<a href="' . $fileUrl . '" class="btn btn-link p-0" target="_blank">View Proof Image</a> | ';
                } else if ($uploadedFile->getType() == 'PROOF_FILE') {
                    $content .= '<a href="' . $fileUrl . '" class="btn btn-link p-0" target="_blank">View Proof File</a> | ';
                } else {
                    $content .= '<a href="' . $fileUrl . '" class="btn btn-link p-0" target="_blank">View File</a><br/>';
                }
            }
        } else {
            $content .= "No proof links available.<br/>";
        }

        $message = $this->addOrderMessage($order, $reason);
        $this->orderLogger->setOrder($order);
        $this->orderLogger->log($content);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->addFlash('success', 'Order status has been updated successfully!');
        $this->eventDispatcher->dispatch(new OrderChangesRequestedEvent($order, $message), OrderChangesRequestedEvent::NAME);

        $this->entityManager->refresh($this->order);

        // if ($this->order->getProofRequestChangeCountAfterApproval() >= OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_AFTER_APPROVAL) {
        //     $this->orderService->revisionItemCharge($this->order);
        // }

        return $this->redirectToRoute('admin_order_proofs', ['orderId' => $order->getOrderId()]);
    }

    private function addOrderMessage(Order $order, $reason): OrderMessage
    {
        $message = new OrderMessage();
        $message->setOrder($order);
        $message->setType(OrderStatusEnum::CHANGES_REQUESTED);
        $message->setContent($reason);
        $message->setSentBy($this->getUser());
        $this->entityManager->persist($message);
        return $message;
    }
}