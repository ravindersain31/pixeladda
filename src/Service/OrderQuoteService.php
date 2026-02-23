<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\EmailQuote;
use App\Enum\StoreConfigEnum;
use App\Repository\EmailQuoteRepository;
use App\Repository\StoreDomainRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Service\StoreInfoService;

class OrderQuoteService
{

    public function __construct(
        private readonly MailerInterface       $mailer,
        private readonly UserService           $userService,
        private readonly EmailQuoteRepository  $emailQuoteRepository,
        private readonly StoreInfoService      $storeInfoService,
        private readonly StoreDomainRepository   $storeDomainRepository,
    )
    {
    }

    public function save(Cart $cart, string $email): void
    {
        $emailQuote = new EmailQuote();
        $emailQuote->setCart($cart);
        $data['email'] = $email;
        $user = $this->userService->getUserFromAddress($data);
        $emailQuote->setUser($user);
        $host = $this->storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $this->storeDomainRepository->findByDomain($host);
        $emailQuote->setStoreDomain($storeDomain);

        $this->emailQuoteRepository->save($emailQuote, true);

        $this->sendMail($cart, $email);
    }

    private function sendMail(Cart $cart, string $email): void
    {
        $storeName = $this->storeInfoService->getStoreName();
        $message = (new TemplatedEmail());
        $message->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $message->subject("Your Order Quote has been saved");
        $message->to(new Address($email));
        $message->cc(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
        $message->htmlTemplate('emails/order_quote.html.twig')->context([
            'cart' => $cart,
        ]);

        $this->mailer->send($message);
    }

}