<?php

namespace App\EventListener;

use App\Entity\AppUser;
use App\Entity\Order;
use App\Entity\OrderLog;
use App\Enum\OrderStatusEnum;
use App\Enum\ShippingEnum;
use App\Enum\StoreConfigEnum;
use App\Event\OrderCancelledEvent;
use App\Event\OrderChangesRequestedEvent;
use App\Event\OrderProofApprovedEvent;
use App\Event\OrderProofUploadedEvent;
use App\Event\OrderReceivedEmailEvent;
use App\Event\OrderReceivedEvent;
use App\Event\OrderShippedEvent;
use App\Service\KlaviyoService;
use App\Service\OrderDeliveryDateService;
use App\Service\ReferralService;
use App\Service\SlackManager;
use App\Service\SubscriberService;
use App\Service\TwilioService;
use App\SlackSchema\NewOrderSchema;
use App\SlackSchema\NewProofUploadedSchema;
use App\SlackSchema\RequestedChangesSchema;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\Reward\RewardService;
use App\Enum\PaymentStatusEnum;
use App\SlackSchema\OrderApprovedSchema;
use App\Service\StoreInfoService;
use App\Service\GoogleDriveService;
use App\Service\OrderLogger;

readonly class OrderEventListener
{
    public function __construct(
        private SlackManager             $slackManager,
        private UrlGeneratorInterface    $urlGenerator,
        private MailerInterface          $mailer,
        private EntityManagerInterface   $entityManager,
        private OrderDeliveryDateService $deliveryDateService,
        private SubscriberService        $subscriberService,
        private TwilioService            $twilioService,
        private RewardService            $rewardService,
        private ReferralService          $referralService,
        private KlaviyoService           $klaviyoService,
        private StoreInfoService         $storeInfoService,
        private GoogleDriveService       $driveService,
        private OrderLogger              $orderLogger,
    ) {}

    #[AsEventListener(event: OrderReceivedEvent::NAME)]
    public function onOrderReceived(OrderReceivedEvent $event): void
    {
        $order = $event->getOrder();

        $this->slackManager->send(SlackManager::SALES, NewOrderSchema::get($order, [
            'totalSummary' => true,
            'viewOrderLink' => $this->urlGenerator->generate('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'proofsLink' => $this->urlGenerator->generate('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
        ], $this->entityManager));

        if ($order->isSample() || $order->isWireStake() || $order->isWireStakeAndSampleAndBlankSign() || $order->isBlankSign()) {
            // $order->setProofApprovedAt(new \DateTimeImmutable());
            // $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
            // $this->entityManager->persist($order);
            // $this->entityManager->flush();
        } else {
            if ($order->isNeedProof() === false) {
                $this->orderLogger->setOrder($order)->log('Proof pre-approved by customer.', null, OrderLog::PROOF_PRE_APPROVED);
                $this->slackManager->send(SlackManager::ORDER_APPROVED, OrderApprovedSchema::get($order, $this->urlGenerator));
            }
            $this->slackManager->send(SlackManager::DESIGNER, NewOrderSchema::get($order, [
                'totalSummary' => false,
                'showPrice' => false,
                'viewOrderLink' => $this->urlGenerator->generate('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'proofsLink' => $this->urlGenerator->generate('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ], $this->entityManager));
        }

        if ($order->getOrderChannel()->isEmailNotification()) {
            $storeName = $this->storeInfoService->getStoreName();
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->subject("Order Received #" . $order->getOrderId());
            $email->to($this->getEmail($order));
            $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->htmlTemplate('emails/order_received.html.twig')->context([
                'order' => $order,
                'store_url' => StoreConfigEnum::STORE_URL,
                'store_name' => $storeName,
                'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
            ]);

            $this->mailer->send($email);
        }

        if ($event->isDiscountForNextOrder()) {
            if ($order->getOrderChannel()->isEmailNotification()) {
                $storeName = $this->storeInfoService->getStoreName();
                $email = new TemplatedEmail();
                $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $email->subject("Order Today, Save 10% Off");
                $email->to($order->getUser()->getEmail());
                $email->htmlTemplate('emails/order_mail10%off.html.twig')->context([
                    'order' => $order,
                    'store_url' => StoreConfigEnum::STORE_URL,
                    'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
                    'show_unsubscribe_link' => true,
                    'user_email' => $order->getUser()->getEmail()
                ]);
                $this->mailer->send($email);
            }
        }

        // if ($event->isDiscountForNextOrder() || $order->isTextUpdates()) {
        $this->subscriberService->subscribe(
            email: $this->getEmail($order),
            fullName: $order->getBillingAddress()['firstName'] . ' ' . $order->getBillingAddress()['lastName'],
            phone: $order->getTextUpdatesNumber() ?? $order->getBillingAddress()['phone'],
            mobileAlert: $order->isTextUpdates(),
            marketing: $event->isDiscountForNextOrder(),
        );
        // }

        $this->updateRewardPoints($order);
        $user = $order->getCoupon()?->getUser();
        if ($user instanceof AppUser) {
            $this->updateRewardPoints($order, $user);
        }

        $this->klaviyoService->placedOrder($order);

        $driveLink = $this->driveService->createOrderFolder($order->getOrderId());
        if($driveLink) {
            $order->setDriveLink($driveLink);
            $this->entityManager->flush();
        }
    }

    #[AsEventListener(event: OrderReceivedEmailEvent::NAME)]
    public function onOrderReceivedEmail(OrderReceivedEmailEvent $event): void
    {
        $order = $event->getOrder();

        if ($order->getOrderChannel()->isEmailNotification()) {
            $storeName = $this->storeInfoService->getStoreName();
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName), );
            $email->subject("Order Received #" . $order->getOrderId());
            $email->to($this->getEmail($order));
            $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->htmlTemplate('emails/order_received.html.twig')->context([
                'order' => $order,
                'store_url' => StoreConfigEnum::STORE_URL,
                'store_name' => $storeName,
                'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
            ]);

            $this->mailer->send($email);
        }
    }

    #[AsEventListener(event: OrderProofUploadedEvent::NAME)]
    public function onOrderProofUploaded(OrderProofUploadedEvent $event): void
    {
        $order = $event->getOrder();

        if(!$order->getIsApproved() || $event->getSyncDeliveryDate()) {
            $this->deliveryDateService->sync($order);
        }

        $uploadedProof = $event->getUploadedProof();

        if ($order->isSample() || $order->isWireStake() || $order->isWireStakeAndSampleAndBlankSign() || $order->isBlankSign()) {
            if ($order->getPaymentStatus() !== PaymentStatusEnum::PENDING) {
                $order->setProofApprovedAt(new \DateTimeImmutable());
                $order->setApprovedProof($uploadedProof);
                $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                $this->entityManager->persist($order);
                $this->entityManager->flush();

                $this->slackManager->send(SlackManager::ORDER_APPROVED, OrderApprovedSchema::get($order, $this->urlGenerator));
            }
        } else {
            $this->slackManager->send(SlackManager::DESIGNER, NewProofUploadedSchema::get($order, $uploadedProof, [
                'customerProofLink' => $this->urlGenerator->generate('order_proof', ['oid' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'viewOrderLink' => $this->urlGenerator->generate('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'proofsLink' => $this->urlGenerator->generate('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]));
        }

        if(!$order->getIsApproved()) {
            if ($order->getOrderChannel()->isEmailNotification() && !$order->isSample()) {
                $storeName = $this->storeInfoService->getStoreName();
                $proofUploadedMessage = new TemplatedEmail();
                $proofUploadedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $proofUploadedMessage->subject("New Proof Uploaded for Order ID #" . $order->getOrderId());
                $proofUploadedMessage->to($this->getEmail($order));
                $proofUploadedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $proofUploadedMessage->htmlTemplate('emails/order_new_proof.html.twig')->context([
                    'order' => $order,
                    'store_url' => StoreConfigEnum::STORE_URL,
                    'store_name' => $storeName,
                    'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
                    'message' => $uploadedProof
                ]);

                $this->mailer->send($proofUploadedMessage);
            }

            $contactNumber = $order->getTextUpdatesNumber() ?? $order->getBillingAddress()['phone'];
            $smsText = "Yard Sign Plus: Your proof for Order #" . $order->getOrderId() . " is ready. Click here to Approve: " . $this->urlGenerator->generate('order_proof', ['oid' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $callText = "YardSignPlus calling about your order. Your digital proof is ready. Please check your inbox for the proof we just emailed you. For any questions please call (877) 958-1499.";

            if ($order->getOrderChannel()->isSmsNotification()) {
                $this->twilioService->sendSms($contactNumber, $smsText, $order);
            }
            if ($order->getOrderChannel()->isCallNotification()) {
                $this->twilioService->sendVoiceCall($contactNumber, $callText, $order);
            }
        }
    }

    #[AsEventListener(event: OrderProofApprovedEvent::NAME)]
    public function onOrderProofApproved(OrderProofApprovedEvent $event): void
    {
        $order = $event->getOrder();

        if(!$order->getIsApproved()) {

            $this->deliveryDateService->sync($order);
            $approvedProof = $event->getApprovedProof();

            if ($order->getOrderChannel()->isEmailNotification() && !$order->isSample()) {
                $storeName = $this->storeInfoService->getStoreName();
                $approvedProofMessage = new TemplatedEmail();
                $approvedProofMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $approvedProofMessage->subject("Proof Approved for Order ID #" . $order->getOrderId());
                $approvedProofMessage->to($this->getEmail($order));
                $approvedProofMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $approvedProofMessage->htmlTemplate('emails/order_proof_approved.html.twig')->context([
                    'order' => $order,
                    'store_url' => StoreConfigEnum::STORE_URL,
                    'store_name' => $storeName,
                    'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
                    'message' => $approvedProof
                ]);

                $this->mailer->send($approvedProofMessage);
            }
        }
    }

    #[AsEventListener(event: OrderChangesRequestedEvent::NAME)]
    public function onOrderChangeRequested(OrderChangesRequestedEvent $event): void
    {
        $order = $event->getOrder();

        if(!$order->getIsApproved()) {
            $this->deliveryDateService->sync($order);
        }

        $changesRequested = $event->getChangesRequested();

        $this->slackManager->send(SlackManager::DESIGNER, RequestedChangesSchema::get($order, $changesRequested->getContent(), [
            'viewOrderLink' => $this->urlGenerator->generate('admin_order_overview', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'proofsLink' => $this->urlGenerator->generate('admin_order_proofs', ['orderId' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL)
        ]));

        if(!$order->getIsApproved()) {
            if ($order->getOrderChannel()->isEmailNotification()) {
                $storeName = $this->storeInfoService->getStoreName();
                $changesRequestedMessage = new TemplatedEmail();
                $changesRequestedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $changesRequestedMessage->subject("Changes Requested for Order ID #" . $order->getOrderId());
                $changesRequestedMessage->to($this->getEmail($order));
                $changesRequestedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $changesRequestedMessage->htmlTemplate('emails/order_changes_requested.html.twig')->context([
                    'order' => $order,
                    'message' => $changesRequested,
                ]);

                $this->mailer->send($changesRequestedMessage);
            }
        }
    }

    #[AsEventListener(event: OrderShippedEvent::NAME)]
    public function onOrderShipped(OrderShippedEvent $event): void
    {
        $order = $event->getOrder();
        $storeTitle = $this->storeInfoService->storeInfo($order)['storeTitle'];

        if ($order->getOrderChannel()->isEmailNotification()) {
            $storeName = $this->storeInfoService->getStoreName();
            $shippedMessage = new TemplatedEmail();
            $shippedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $shippedMessage->subject("Track Your Order from ". $storeTitle .".com #" . $order->getOrderId());
            $shippedMessage->to($this->getEmail($order));
            $shippedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $shippedMessage->htmlTemplate('emails/order_shipped.html.twig')->context([
                'order' => $order,
            ]);
            $this->mailer->send($shippedMessage);
        }

        $this->klaviyoService->fulfilledOrder($order);
    }

    #[AsEventListener(event: OrderCancelledEvent::NAME)]
    public function orderCancelled(OrderCancelledEvent $event): void
    {
        $order = $event->getOrder();

        if ($order->getOrderChannel()->isEmailNotification()) {
            $storeName = $this->storeInfoService->getStoreName();
            $shippedMessage = new TemplatedEmail();
            $shippedMessage->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $shippedMessage->subject("Order Cancelled #" . $order->getOrderId());
            $shippedMessage->to($this->getEmail($order));
            $shippedMessage->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $shippedMessage->htmlTemplate('emails/order_cancelled.html.twig')->context([
                'order' => $order,
            ]);

            $this->mailer->send($shippedMessage);
        }

        $this->klaviyoService->cancelledOrder($order);
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

    public function updateRewardPoints(Order $order , ?AppUser $user = null): void
    {
        $this->rewardService->updateOrderRewardPoints($order, $user);
    }
}