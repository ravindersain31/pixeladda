<?php

namespace App\Payment\Braintree;

use App\Entity\AppUser;
use App\Entity\Order;
use App\Payment\PaymentInterface;
use Braintree\Result\Error;
use Braintree\Result\Successful;

class GooglePay extends Braintree
{

}