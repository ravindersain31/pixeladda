<?php

namespace App\Event;

use App\Entity\Order;
use App\Entity\OrderMessage;
use Symfony\Contracts\EventDispatcher\Event;

class OrderProofApprovedEvent extends Event
{

    const NAME = 'order.proof.approved';

    public function __construct(private readonly Order $order, private readonly OrderMessage $approvedProof)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getApprovedProof(): OrderMessage
    {
        return $this->approvedProof;
    }


}