<?php

namespace App\Event;

use App\Entity\Order;
use App\Entity\OrderMessage;
use Symfony\Contracts\EventDispatcher\Event;

class OrderChangesRequestedEvent extends Event
{

    const NAME = 'order.proof.changes-requested';

    public function __construct(private readonly Order $order, private readonly OrderMessage $changesRequested)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getChangesRequested(): OrderMessage
    {
        return $this->changesRequested;
    }


}