<?php

namespace App\Controller\Web\Component;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Form\RequestChangesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "RequestChangeForm",
    template: "account/order/component/request-changes-form.html.twig"
)]
class RequestChangeComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'order')]
    public ?Order $order;

    #[LiveProp(fieldName: 'data')]
    public ?array $data;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RequestChangesType::class, $this->data);
    }

    public function getChargeProofCountText(): string
    {
        $changesCount = $this->countOrderMessagesByType(OrderStatusEnum::CHANGES_REQUESTED);
        $proofCount = $this->countOrderMessagesByType(OrderStatusEnum::PROOF);


        $totalAmount = (float) str_replace(',', '', $this->getTotalAmount($this->getAmountToBeCharged()));

        if ($totalAmount >= 20) {
            return 'Payment for Proof #' . $changesCount . ', #' . $proofCount;
        } else {
            return 'Payment for Proof #' . $changesCount;
        }
    }

    public function getTotalAmount(?float $additionalAmount = null): string
    {
        $order = $this->order;

        $total = $order->getTotalAmount() + $order->getRefundedAmount();

        if ($additionalAmount !== null) {
            $total += $additionalAmount;
        }

        $total -= $order->getTotalReceivedAmount();

        return number_format($total, 2);
    }

    private function countOrderMessagesByType(string $type): int
    {
        return $this->order->getOrderMessages()->filter(
            fn(OrderMessage $message) => $message->getType() === $type
        )->count();
    }

    public function getAmountToBeCharged(): float
    {
        if ($this->order->getProofRequestChangeCountAfterApproval() >= OrderStatusEnum::MAX_REQUEST_CHANGES_COUNT_AFTER_APPROVAL) {
            return OrderStatusEnum::CHARGE_FEE;
        }else{
            return OrderStatusEnum::CHARGE_FEE;
        }
    }
}
