<?php

namespace App\Event;

use App\Entity\Order;
use App\Entity\OrderShipment;
use Symfony\Contracts\EventDispatcher\Event;

class OrderOutForDeliveryEvent extends Event
{

    const NAME = 'order.shipment.out_for_delivery';

    public function __construct(private readonly Order $order, private readonly OrderShipment $orderShipment)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getOrderShipment(): OrderShipment
    {
        return $this->orderShipment;
    }


}