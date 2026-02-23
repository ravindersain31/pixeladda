<?php 

namespace App\MessageHandler;

use App\Entity\Order;
use App\Enum\StoreConfigEnum;
use App\Message\SendReviewEmailMessage;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendReviewEmailMessageHandler
{
    public function __construct(private StoreInfoService $storeInfoService, private EntityManagerInterface $em, private MailerInterface $mailer, private LockFactory $lockFactory) 
    {
    }

    public function __invoke(SendReviewEmailMessage $message)
    {
        $lockKey = 'send_review_email_' . $message->orderId;
        $lock = $this->lockFactory->createLock($lockKey, ttl: 120); 

        if (!$lock->acquire()) {
            return;
        }

        try {
            $order = $this->em->getRepository(Order::class)->find($message->orderId);
            if (!$order) return;

            $billingAddress = $order->getBillingAddress();
            $emailAddress = $billingAddress['email'];

            switch ($message->reviewType) {
                case 'review':
                    $subject = 'Leave a Google Review, Receive 5% Off Your Order!';
                    $template = 'emails/order_leave_a_review.html.twig';
                    $order->setLeaveAReviewSentAt(new \DateTimeImmutable());
                    break;

                case 'photo_review':
                    $subject = 'Share Your Order Photos, Receive 5% in YSP Rewards!';
                    $template = 'emails/review/photo_review.html.twig';
                    $order->setLeaveAPhotoReviewSentAt(new \DateTimeImmutable());
                    break;

                case 'video_review':
                    $subject = 'Share a Video Review, Receive 5% Off!';
                    $template = 'emails/review/video_review.html.twig';
                    $order->setLeaveAVideoReviewSentAt(new \DateTimeImmutable());
                    break;

                default:
                    return;
            }
            $storeName = $this->storeInfoService->getStoreName();
            $email = (new TemplatedEmail())
                ->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName))
                ->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName))
                ->to($emailAddress)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context(['order' => $order]);

            $this->mailer->send($email);

            $this->em->persist($order);
            $this->em->flush();
        } finally {
            $lock->release();
        }
    }
}