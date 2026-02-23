<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\SavedDesign;
use App\Enum\KlaviyoEvent;
use App\Enum\StoreConfigEnum;
use App\Repository\SavedDesignRepository;
use App\Repository\StoreDomainRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;

class SaveDesignService
{

    public function __construct(
        private readonly MailerInterface       $mailer,
        private readonly UserService           $userService,
        private readonly SavedDesignRepository $savedDesignRepository,
        private readonly KlaviyoService        $klaviyoService,
        private readonly StoreInfoService      $storeInfoService,
        private readonly EntityManagerInterface $entityManager,
        private readonly StoreDomainRepository  $storeDomainRepository,
    )
    {
    }

    public function save(Cart $cart, string $email): void
    {
        $host = $this->storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $this->storeDomainRepository->findByDomain($host);
        $savedDesign = new SavedDesign();
        $savedDesign->setCart($cart);
        $data['email'] = $email;
        $user = $this->userService->getUserFromAddress($data);
        $savedDesign->setUser($user);
        $savedDesign->setStoreDomain($storeDomain);

        $this->savedDesignRepository->save($savedDesign, true);

        $this->sendMail($cart, $email);
        $this->klaviyoService->saveCartDesignQuote($cart, KlaviyoEvent::SAVE_YOUR_DESIGN, $email);
    }

    private function sendMail(Cart $cart, string $email): void
    {
        $storeName = $this->storeInfoService->getStoreName();
        $message = (new TemplatedEmail());
        $message->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $message->subject("Your Design Has Been Saved");
        $message->to(new Address($email));
        $message->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $message->htmlTemplate('emails/save_design.html.twig')->context([
            'cart' => $cart,
        ]);

        $this->mailer->send($message);
    }

}