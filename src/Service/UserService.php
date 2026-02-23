<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Reward\Reward;
use App\Entity\User;
use App\Enum\StoreConfigEnum;
use App\Event\RewardRedeemEvent;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Service\StoreInfoService;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        public readonly EventDispatcherInterface $eventDispatcher,
        private readonly StoreInfoService      $storeInfoService,
    )
    {
    }

    public function getUserFromAddress(array $address): ?AppUser
    {
        if (!isset($address['email'])) {
            return null;
        }
        $email = $address['email'];
        $firstName = $address['first_name'] ?? null;
        $lastName = $address['last_name'] ?? null;
        $user = $this->entityManager->getRepository(AppUser::class)->findOneBy(['email' => $email]);
        if (!$user instanceof AppUser) {
            $user = new AppUser();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setName($firstName . ' ' . $lastName);
            $user->setUsername($email);
            $user->setRoles(['ROLE_USER']);
            $newPass = bin2hex(random_bytes(4));
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPass);
            $user->setPassword($hashedPassword);
            $user->setIsEnabled(false);
            $reward = new Reward();
            $user->setReward($reward);
            $reward->setUser($user);
            $this->entityManager->persist($reward);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->sendRedeemRewardEmail($reward, $user);
        }
        return $user;
    }

    public function createResetPasswordRequest(User $user, string $userType = 'customer'): void
    {
        $randomString = bin2hex(random_bytes(18)) . uniqid();
        $user->setResetToken($randomString);
        $user->setResetTokenExpireAt((new \DateTimeImmutable())->modify('+6 hours'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $storeName = $this->storeInfoService->getStoreName();
        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
        $email->subject("Yard Sign Plus Password Reset Request");
        $email->to($user->getEmail());
        $email->htmlTemplate('emails/reset-password.html.twig')->context([
            'user' => $user,
            'userType' => $userType,
        ]);
        $this->mailer->send($email);
    }

    private function sendRedeemRewardEmail(Reward $reward, AppUser $user): void
    {
        $this->eventDispatcher->dispatch(new RewardRedeemEvent($reward, $user), RewardRedeemEvent::NAME);
    }

    private function sendNewUserEmail(AppUser $user, string $newPass): void
    {
        try {
            $storeName = $this->storeInfoService->getStoreName();
            $email = (new TemplatedEmail());
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
            $email->subject("New Account - " .  $storeName);
            $email->to($user->getEmail());
            $email->htmlTemplate('emails/welcome.html.twig')->context([
                'customer' => $user,
                'password' => $newPass,
                'store_url' => StoreConfigEnum::STORE_URL,
            ]);
            $this->mailer->send($email);

        } catch (Exception $e) {

        }
    }


    public function createUserWithEmailAndAddress(string $email, array $billingAddress): ?AppUser
    {
        if (!isset($email)) {
            return null;
        }
        $user = new AppUser();
        $user->setEmail($email);
        $user->setUsername($email);

        $user->setFirstName($billingAddress['firstName']);
        $user->setLastName($billingAddress['lastName']);
        $user->setName(($billingAddress['firstName']) . ' ' . ($billingAddress['lastName']));
        $user->setRoles(['ROLE_USER']);
        $user->setMobile($billingAddress['phone']);
        $newPass = bin2hex(random_bytes(4));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPass);
        $user->setPassword($hashedPassword);

        $user->setIsEnabled(false);
        $reward = new Reward();
        $user->setReward($reward);
        $reward->setUser($user);
        $this->entityManager->persist($reward);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

}