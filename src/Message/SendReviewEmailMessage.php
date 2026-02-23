<?php 

namespace App\Message;

class SendReviewEmailMessage
{
    public function __construct(public int $orderId, public string $reviewType) 
    {
    }
}
