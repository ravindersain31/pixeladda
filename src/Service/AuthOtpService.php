<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use App\Enum\StoreConfigEnum;
use App\Service\StoreInfoService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class AuthOtpService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private PasswordHasherFactoryInterface $hasherFactory,
        private StoreInfoService $storeInfoService,
    ) {}

    public function generateAndSendOtp(User $user): void
    {
        $otp = (string) random_int(1000, 9999);
        $expiresAt = new \DateTimeImmutable('+10 minutes');

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hash = $hasher->hash($otp);

        $user->setOtpHash($hash);
        $user->setOtpExpiresAt($expiresAt);
        $this->em->persist($user);
        $this->em->flush();

        $this->sendOtpMail($user, $otp);
    }


    private function sendOtpMail(AppUser $user, string $otp): void
    {
        $storeName = $this->storeInfoService->getStoreName();

        $email = (new TemplatedEmail())
            ->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName))
            ->to($user->getEmail())
            ->subject('Your One-Time Password (OTP)')
            ->htmlTemplate('emails/otp_email.html.twig')
            ->context([
                'user_name' => $user->getFirstName() ?? 'Customer',
                'otp' => $otp,
                'store_name' => $storeName,
            ]);

        $this->mailer->send($email);
    }

    public function verifyOtp(User $user, string $otp): bool
    {

        if (!$user->getOtpHash() || !$user->getOtpExpiresAt()) {
            return false;
        }

        if ($user->getOtpExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        if ($hasher->verify($user->getOtpHash(), $otp)) {

            $user->setOtpHash(null);
            $user->setOtpExpiresAt(null);

            $this->em->flush();
            return true;
        }

        return false;
    }
}
