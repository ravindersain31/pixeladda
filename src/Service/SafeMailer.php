<?php

namespace App\Service;

use App\Schema\ErrorLogSchema;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

class SafeMailer implements MailerInterface
{

    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        try {
            $this->mailer->send($message, $envelope);
        } catch (TransportExceptionInterface $exception) {
            
            // Log the error to Slack
        }
    }
}