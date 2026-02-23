<?php

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderReceivedEvent extends Event
{

    const NAME = 'order.received';

    public function __construct(private readonly Order $order, private readonly bool $discountForNextOrder)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function isDiscountForNextOrder(): bool
    {
        return $this->discountForNextOrder;
    }


}