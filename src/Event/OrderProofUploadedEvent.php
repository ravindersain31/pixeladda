<?php

namespace App\Event;

use App\Entity\Order;
use App\Entity\OrderMessage;
use Symfony\Contracts\EventDispatcher\Event;

class OrderProofUploadedEvent extends Event
{

    const NAME = 'order.proof.uploaded';

    public function __construct(private readonly Order $order, private readonly OrderMessage $uploadedProof, private readonly bool $syncDeliveryDate = false)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getUploadedProof(): OrderMessage
    {
        return $this->uploadedProof;
    }

    public function getSyncDeliveryDate(): bool
    {
        return $this->syncDeliveryDate;
    }

}