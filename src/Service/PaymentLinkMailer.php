<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Enum\StoreConfigEnum;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentLinkMailer
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private ParameterBagInterface $parameterBag;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $parameterBag, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
    }

    public function sendPaymentReceivedEmail(Order $order, OrderTransaction $paymentRequest): void
    {
        $billingAddress = $order->getBillingAddress();
        $billingAddressText = $billingAddress['firstName'] . ' ' . $billingAddress['lastName'];

        $adminHost = $this->parameterBag->get('APP_ADMIN_HOST');
        $orderViewLink = rtrim($adminHost, '/') . $this->urlGenerator->generate( 'admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_PATH );
        $email = (new TemplatedEmail())
            ->to(new Address(StoreConfigEnum::SALES_EMAIL, StoreConfigEnum::STORE_NAME))
            ->cc(new Address(StoreConfigEnum::SALES_EMAIL, StoreConfigEnum::STORE_NAME))
            ->from(new Address(StoreConfigEnum::SALES_EMAIL, StoreConfigEnum::STORE_NAME))
            ->subject('Payment Successfully Received For Order #' . $order->getOrderId())
            ->htmlTemplate('emails/payment_received.html.twig')
            ->context([
                'order' => $order,
                'orderViewLink' => $orderViewLink,
                'billingAddressText' => $billingAddressText,
                'paymentRequest' => $paymentRequest,
            ]);

        $this->mailer->send($email);
    }
}
