<?php

namespace App\Payment\SeeDesignPayLater;

use App\Entity\Order;
use App\Payment\AbstractPayment;
use App\Payment\PaymentInterface;
use Braintree\Gateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SeeDesignPayLater extends AbstractPayment implements PaymentInterface
{

    private ?string $actionOnSuccess = null;

    public function charge(Order $order): array
    {
        return [
            'success' => true,
            'action' => 'pending',
            'message' => 'See Design Pay Later.',
        ];
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }
}