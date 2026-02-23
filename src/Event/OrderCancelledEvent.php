<?php

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderCancelledEvent extends Event
{

    const NAME = 'order.cancelled';

    public function __construct(private readonly Order $order, private readonly bool $refunded)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function isRefunded(): bool
    {
        return $this->refunded;
    }

}