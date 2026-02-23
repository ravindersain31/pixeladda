<?php

namespace App\Controller\Cron;

use App\Entity\AppUser;
use App\Entity\Reward\Reward;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Enum\StoreConfigEnum;
use App\Service\StoreInfoService;
use Symfony\Component\Mailer\MailerInterface;

class RewardController extends AbstractController
{
    public function __construct(
        private readonly StoreInfoService $storeInfoService,
    ) {}

    #[Route('/redeem-rewards', name: 'cron_redeem_rewards')]
    public function index(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer,): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        $usersWithAvailableRewards = $entityManager->getRepository(Reward::class)->findUsersWithAvailableRewards();
        /** @var Reward $reward */
        $storeName = $this->storeInfoService->getStoreName();
        foreach($usersWithAvailableRewards as $reward) {
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->subject("Create My Account to Redeem FREE YSP Rewards!");
            $email->to($reward->getUser()->getEmail());
            $email->htmlTemplate('emails/reward/redeem_reward.html.twig')->context([
                'show_unsubscribe_link' => true,
                'user_email' => $reward->getUser()->getEmail()
            ]);
            $mailer->send($email);
        }

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }

    #[Route('/expiry-rewards', name: 'cron_expiry_rewards')]
    public function expireRewards(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer,): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        $usersWithAvailableRewards = $entityManager->getRepository(Reward::class)->findUsersWithAvailableRewards();
        /** @var Reward $reward */
        $mailSentCount = 0;
        $storeName = $this->storeInfoService->getStoreName();
        foreach($usersWithAvailableRewards as $reward) {
            $expiryMinusSevenDays = $reward->getExpiryAt()->modify('-7 days');
            if ($expiryMinusSevenDays->format('Y-m-d') === $date->format('Y-m-d')) {
                $email = new TemplatedEmail();
                $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
                $email->subject("Your YSP Rewards Expire Soon, Use Now!");
                $email->to($reward->getUser()->getEmail());
                $email->htmlTemplate('emails/reward/expiry_reward.html.twig')->context([
                    'reward' => $reward,
                ]);
                $mailer->send($email);
                $mailSentCount++;
            }
        }

        return $this->json([
            'status' => 'ok',
            'date' => $date->format('Y-m-d'),
            'report' => [
                'mail_sent_count' => $mailSentCount
            ]
        ]);
    }
}
