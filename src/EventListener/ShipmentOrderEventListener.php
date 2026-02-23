<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\OrderShipment;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\StoreConfigEnum;
use App\Event\OrderDeliveredEvent;
use App\Event\OrderOutForDeliveryEvent;
use App\Service\OrderLogger;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ShipmentOrderEventListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLogger            $orderLogger,
        private readonly MailerInterface        $mailer,
        private readonly StoreInfoService       $storeInfoService,
    )
    {
    }

    #[AsEventListener(event: OrderOutForDeliveryEvent::NAME)]
    public function onOrderOutForDelivery(OrderOutForDeliveryEvent $event): void
    {
        $order = $event->getOrder();
        $this->orderLogger->setOrder($order);
        $shipment = $event->getOrderShipment();
        $this->orderLogger->log('Shipment with Tracking Id: <b>' . $shipment->getTrackingId() . '</b> is out for delivery.');

        $isOutForDeliveryEmailSent = $order->getMetaDataKey('isOutForDeliveryEmailSent');
        if (!$isOutForDeliveryEmailSent && !is_bool($isOutForDeliveryEmailSent)) {
            $isOutForDeliveryEmailSent = filter_var($isOutForDeliveryEmailSent, FILTER_VALIDATE_BOOLEAN);
        }
        if ($order->getOrderChannel()->isEmailNotification() && !$isOutForDeliveryEmailSent) {
            $storeName = $this->storeInfoService->getStoreName();
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->subject("Your Order is Out for Delivery #" . $order->getOrderId());
            $email->to($this->getEmail($order));
            $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->htmlTemplate('emails/order_out_for_delivery.html.twig')->context([
                'order' => $order,
            ]);

            $this->mailer->send($email);
            $order->setMetaDataKey('isOutForDeliveryEmailSent', true);
        }
    }

    #[AsEventListener(event: OrderDeliveredEvent::NAME)]
    public function onOrderDelivered(OrderDeliveredEvent $event): void
    {
        $order = $event->getOrder();
        $this->orderLogger->setOrder($order);
        $shipment = $event->getOrderShipment();
        $this->orderLogger->log('Shipment with Tracking Id: <b>' . $shipment->getTrackingId() . '</b> has been delivered.');

        $isOrderAllShipmentsDelivered = $this->entityManager->getRepository(OrderShipment::class)->isOrderAllShipmentsDelivered($order);

        $isWareOrderIsDone = $order->getWarehouseOrder() === null ? true : $order->getWarehouseOrder()->getPrintStatus() === WarehouseOrderStatusEnum::DONE;

        if ($isOrderAllShipmentsDelivered && $isWareOrderIsDone) {
            $order->setStatus(OrderStatusEnum::COMPLETED);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->orderLogger->log('Order has been completed as it has been delivered.');
        }

        $isDeliveredEmailSent = $order->getMetaDataKey('isDeliveredEmailSent');
        if (!$isDeliveredEmailSent && !is_bool($isDeliveredEmailSent)) {
            $isDeliveredEmailSent = filter_var($isDeliveredEmailSent, FILTER_VALIDATE_BOOLEAN);
        }
        if ($order->getOrderChannel()->isEmailNotification() && !$isDeliveredEmailSent) {
            $storeName = $this->storeInfoService->getStoreName();
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->subject("Your Order has been Delivered #" . $order->getOrderId());
            $email->to($this->getEmail($order));
            $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->htmlTemplate('emails/order_delivered.html.twig')->context([
                'order' => $order,
            ]);

            $this->mailer->send($email);
            $order->setMetaDataKey('isDeliveredEmailSent', true);
        }
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