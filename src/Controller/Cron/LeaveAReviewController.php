<?php

namespace App\Controller\Cron;

use App\Entity\Order;
use App\Message\SendReviewEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class LeaveAReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus
    ){
    }

    #[Route(path: '/leave-a-review', name: 'cron_leave_a_review', methods: ['GET'])]
    public function leaveAReview(Request $request): Response
    {
        return $this->processOrdersAndSendEmails($request, 'review');
    }

    #[Route(path: '/leave-a-photo-review', name: 'cron_leave_a_photo_review', methods: ['GET'])]
    public function leaveAPhotoReview(Request $request): Response
    {
        return $this->processOrdersAndSendEmails($request, 'photo_review');
    }

    #[Route(path: '/leave-a-video-review', name: 'cron_leave_a_video_review', methods: ['GET'])]
    public function leaveAVideoReview(Request $request): Response
    {
        return $this->processOrdersAndSendEmails($request, 'video_review');
    }

    private function processOrdersAndSendEmails(Request $request, string $type): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        $chunkSize = 50;
        $offset = 0;
        $emailsSentCount = 0;
        $errors = [];

        do {
            $orderIds = $this->entityManager->getRepository(Order::class)->leaveAReview($date, $type, $chunkSize, $offset);

            foreach ($orderIds as $orderId) {
                try {
                    $this->messageBus->dispatch(new SendReviewEmailMessage($orderId, $type));
                    $emailsSentCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'orderId' => $orderId,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $offset += $chunkSize;
        } while (count($orderIds) === $chunkSize); 



        return $this->json([
            'status' => 'ok',
            'date' => $date->format('Y-m-d H:i:s'),
            'type' => $type,
            'emailsSent' => $emailsSentCount,
            'totalOrders' => $emailsSentCount + count($errors),
            'errors' => $errors,
        ]);
    }

}
