<?php

namespace App\Payment\Braintree;

use App\Entity\Order;
use App\Payment\AbstractPayment;
use App\Payment\PaymentInterface;
use Braintree\Gateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Base extends AbstractPayment
{
    protected Gateway $gateway;

    public function __construct(private readonly ParameterBagInterface $parameterBag, protected readonly EntityManagerInterface $entityManager)
    {
        $this->gateway = new Gateway([
            'environment' => $this->parameterBag->get('BRAINTREE_ENV'),
            'merchantId' => $this->parameterBag->get('BRAINTREE_MERCHANT_ID'),
            'publicKey' => $this->parameterBag->get('BRAINTREE_PUBLIC_KEY'),
            'privateKey' => $this->parameterBag->get('BRAINTREE_PRIVATE_KEY'),
        ]);
    }

}