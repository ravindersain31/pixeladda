<?php

namespace App\Event;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Entity\OrderTransactionRefund;
use Symfony\Contracts\EventDispatcher\Event;

class TransactionRefundEvent extends Event
{

    const NAME = 'order.transaction.refund';

    public function __construct(private readonly Order $order, private readonly OrderTransaction $transaction, private readonly OrderTransactionRefund $refund)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getTransaction(): OrderTransaction
    {
        return $this->transaction;
    }

    public function getRefund(): OrderTransactionRefund
    {
        return $this->refund;
    }
    
}