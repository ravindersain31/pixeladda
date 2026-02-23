<?php

namespace App\Controller\Cron;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\StoreConfigEnum;
use App\Repository\OrderRepository;
use App\Service\OrderDeliveryDateService;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Address;

class ProofReminderController extends AbstractController
{
    public function __construct(
        private readonly OrderDeliveryDateService   $orderDeliveryDateService,
        private readonly MailerInterface            $mailer,
        private readonly OrderRepository            $orderRepository,
        private readonly EntityManagerInterface     $entityManager,
        private readonly StoreInfoService           $storeInfoService,
    ){

    }

    private const MAX_REMINDERS = 0;
    private const REMINDER_INTERVAL_HOURS = 24;
    private const VERSION = 'V2';

    #[Route(path: '/proof-reminder', name: 'cron_proof_reminder')]
    public function index(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        // Fetch orders with proof uploaded status
        $orders = $this->entityManager->getRepository(Order::class)->findOrdersForProofReminders(
            status: [OrderStatusEnum::PROOF_UPLOADED],
        )->getResult();

        /** @var Order $order */
        foreach ($orders as $order) {
            $this->sendProofReminder($order, $date);
        }

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d H:i:s')]);
    }

    private function sendProofReminder(Order $order, \DateTimeImmutable $date): void
    {
        try {
            $email = $this->getEmail($order);

            if (!$email) {
                return;
            }
            
            // Synchronize order delivery date
            $this->orderDeliveryDateService->sync($order);

            // Retrieve last reminder sent time and reminder count
            $lastReminderSent = $order->getLastReminderSent();
            $reminderCount = $order->getReminderCount() ?? 0;

            $now = new \DateTimeImmutable();

            $lastOrderMessage = $order->getOrderMessages()->last();
            if ($lastOrderMessage) {
                $lastOrderMessageSentAt = $lastOrderMessage->getSentAt();
                $interval = $lastOrderMessageSentAt->diff($now);
                $hoursPassedSinceLastMessage = ($interval->days * 24) + $interval->h + ($interval->i / 60) + ($interval->s / 3600);
                // Skip sending email if the last order message was sent less than or equal to 24 hours ago
                if ($hoursPassedSinceLastMessage <= self::REMINDER_INTERVAL_HOURS) {
                    return;
                }
                if ($lastReminderSent !== null && $lastReminderSent >= $lastOrderMessageSentAt) {
                    return;
                }
            }

            // Prepare and send the reminder email
            $storeName = $this->storeInfoService->getStoreName();
            $approvedProofMessage = (new TemplatedEmail())
                ->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName))
                ->to($this->getEmail($order))
                ->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName))
                ->subject("Reminder: Approve or Request Changes on Proof #" . $order->getOrderId())
                ->htmlTemplate('emails/order_new_proof.html.twig')
                ->context([
                    'order' => $order,
                    'store_url' => StoreConfigEnum::STORE_URL,
                    'store_name' => $storeName,
                    'active_storage_host' => StoreConfigEnum::ACTIVE_STORAGE_HOST,
                    'message' => $lastOrderMessage,
                ]);

            $this->mailer->send($approvedProofMessage);

            $order->setLastReminderSent($now);
            $order->setReminderCount($reminderCount + 1);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            echo $this->json([
                'status' => 'error',
                'date' => $date->format('Y-m-d H:i:s'),
                'report' => $e->getMessage(),
            ]);
        }
    }

    private function getEmail(Order $order): ?string
    {
        $billingAddress = $order->getBillingAddress();
        $email = $billingAddress['email'] ?? null;

        if (!$email && $order->getUser()) {
            $email = $order->getUser()->getEmail();
        }

        return $email ?: null; 
    }
}
