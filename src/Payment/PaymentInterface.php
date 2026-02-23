<?php

namespace App\Payment;

use App\Entity\Order;

interface PaymentInterface
{
    public function charge(Order $order): array;

    public function setActionOnSuccess(?string $actionOnSuccess): void;

}