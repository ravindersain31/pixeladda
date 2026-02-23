<?php

namespace App\EventListener;

use App\Event\RewardRedeemEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Enum\StoreConfigEnum;
use App\Service\StoreInfoService;

class RewardEventListener
{
    public function __construct(
        private readonly UrlGeneratorInterface    $urlGenerator,
        private readonly MailerInterface          $mailer,
        private readonly EntityManagerInterface   $entityManager,
        private readonly StoreInfoService         $storeInfoService,
    )
    {
    }

    #[AsEventListener(event: RewardRedeemEvent::NAME)]
    public function onNewUserRedeemReward(RewardRedeemEvent $event): void
    {
        try{
            $storeName = $this->storeInfoService->getStoreName();
            $reward = $event->getReward();
            $user =  $event->getUser();
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->subject("Create My Account to Redeem FREE YSP Rewards!");
            $email->to($user->getEmail());
            $email->htmlTemplate('emails/reward/redeem_reward.html.twig')->context([
                'show_unsubscribe_link' => true,
                'user_email' => $user->getEmail()
            ]);
            $this->mailer->send($email);
        }catch(\Exception $e){
            
        }
    }


}