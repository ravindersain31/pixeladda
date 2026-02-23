<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Enum\StoreConfigEnum;
use App\Event\TransactionRefundEvent;
use App\Service\StoreInfoService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TransactionEventListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MailerInterface       $mailer,
        private readonly StoreInfoService      $storeInfoService,
    )
    {
    }

    #[AsEventListener(event: TransactionRefundEvent::NAME)]
    public function onTransactionRefund(TransactionRefundEvent $event): void
    {
        $order = $event->getOrder();
        $transaction = $event->getTransaction();
        $refund = $event->getRefund();
        $storeName = $this->storeInfoService->getStoreName();
        $refundMessage = new TemplatedEmail();
        $refundMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $refundMessage->subject("Issued Refund Order #" . $order->getOrderId());
        $refundMessage->to($this->getEmail($order));
        $refundMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $refundMessage->htmlTemplate('emails/order_transaction_refund.html.twig')->context([
            'order' => $order,
            'refund' => $refund,
            'transaction' => $transaction,
            'amount' => $refund->getAmount(),
        ]);

        $this->mailer->send($refundMessage);
    }

    private function getEmail(Order $order): string
    {
        $billingAddress = $order->getBillingAddress();
        $orderEmail = $billingAddress['email'];
        if (!$orderEmail) {
            $orderEmail = $order->getUser()->getEmail();
        }
        return $orderEmail;
    }

}