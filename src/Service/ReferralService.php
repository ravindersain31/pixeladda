<?php 

namespace App\Service;

use App\Entity\Admin\Coupon;
use App\Entity\Referral;
use App\Entity\AppUser;
use App\Entity\Order;
use App\Entity\Reward\RewardTransaction;
use App\Entity\Store;
use App\Enum\StoreConfigEnum;
use App\Service\Reward\RewardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ReferralService
{
    public function __construct(private StoreInfoService $storeInfoService, private EntityManagerInterface $entityManager, private MailerInterface $mailer, private RewardService $rewardService)
    {
    }

    public function createReferral(AppUser $referrer, string $referralCode, AppUser $referred, Store $store): void
    {
        $referral = new Referral();
        $referral->setReferrer($referrer);
        $referral->setReferred($referred);
        $referral->setReferralCode($referralCode);
        $this->entityManager->persist($referral);

        $coupon = $this->createCoupon($referred, $store);
        $referral->setCoupon($coupon);

        $this->entityManager->persist($referral);
        $this->entityManager->flush();
    }

    public function createCoupon(AppUser $referred, Store $store): Coupon
    {
        $coupon = new Coupon();
        $coupon->setCouponName('Referral Coupon');
        $coupon->setCode($this->generateUniqueCouponCode());
        $coupon->setDiscount(12);
        $coupon->setType('P'); 
        $coupon->setUsesTotal(1);
        $coupon->setMinCartValue(1);
        $coupon->setEndDate((new \DateTime())->modify('+30 days'));
        $coupon->setIsEnabled(true);
        $coupon->setStore($store);
        $coupon->setCouponType('referral_coupon');

        $this->entityManager->persist($coupon);
        $this->entityManager->flush();

        $this->sendcouponEmail($referred, $coupon);
        return $coupon;

    }

    public function addReferralReward(Order $order): void
    {
        $referral = $this->entityManager->getRepository(Referral::class)->findOneBy([
            'coupon' => $order->getCoupon()
        ]);

        if ($referral) {
            $this->rewardService->updateRewardPoints(
                reward: $referral->getReferrer()->getReward(),
                points: 5,
                comment: 'Received a Referral Reward',
                type: RewardTransaction::CREDIT,
                user: $referral->getReferrer(),
                status: RewardTransaction::STATUS_COMPLETED,
                actionType: RewardTransaction::ORDER_CREDITS_REFERRAL_REWARD,
                order: $order
            );
        }
    }

    public function generateUniqueCouponCode(int $length = 8): string
    {
        $couponCodeBase = $this->generateRandomString($length);

        if ($this->isCouponCodeExists($couponCodeBase)) {
            return $this->generateUniqueCouponCode($length);
        }

        return $couponCodeBase;
    }

    private function generateRandomString(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    private function isCouponCodeExists(string $couponCode): bool
    {
        return (bool) $this->entityManager
            ->getRepository(Coupon::class)
            ->findOneBy(['code' => $couponCode]);
    }

    private function sendcouponEmail(AppUser $referred, Coupon $coupon): void
    {
        $storeName = $this->storeInfoService->getStoreName();
        $email = (new TemplatedEmail())
            ->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName))
            ->subject('You Have Received a Referral Coupon from ' . $storeName)
            ->to($referred->getEmail())
            ->htmlTemplate('emails/referral_coupon.html.twig')->context([
                'customer' => $referred,
                'coupon' => $coupon,
            ]);

        $this->mailer->send($email);
    }
}
