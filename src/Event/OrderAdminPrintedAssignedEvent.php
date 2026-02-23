<?php

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderAdminPrintedAssignedEvent extends Event
{

    const NAME = 'order.admin.printed.assigned';

    public function __construct(private readonly Order $order)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }


}