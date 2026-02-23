<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Enum\StoreConfigEnum;
use App\Repository\OrderRepository;
use App\Service\StoreInfoService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerEventSubscriber implements EventSubscriberInterface
{
    private bool $isTestEmail = false;

    private array|string $testEmailTo = StoreConfigEnum::SALES_EMAIL;

    private array $whiteListedDomains = [
        'yardsignplus.com',
        'sportsgearswag.com',
        'geekybones.com',
        'geeky.dev',
    ];

    private array $whiteListedDevsEmails = [
        'smanojsaini@gmail.com',
        'gautamsinghcpj@gmail.com',
    ];

    private array $whiteListedTesterEmails = [
        'anilkumar.official786@gmail.com',
        'kumaranil.offiical786@gmail.com',
        'anilkukkar20@gmail.com',
        'aboys88@gmail.com',
        'soniaofficial390@gmail.com',
        'nikhiliofficial42@gmail.com',
        'vishalsachdeva.ppk@gmail.com',
        'nitashaabhardwaj@gmail.com',
        'yoursharmah@gmail.com',
    ];

    public function __construct(ParameterBagInterface $parameterBag, private OrderRepository $orderRepository,  private StoreInfoService $storeInfoService,)
    {
        $appEnv = $parameterBag->get('APP_ENV');

        $catchAllTo = $parameterBag->get('MAILER_CATCHALL_TO');
        $emails = explode(',', $catchAllTo);
        $isEmailsValid = $this->isValidEmail($emails);

        if ($appEnv !== 'prod' || ($catchAllTo && $isEmailsValid)) {
            $this->isTestEmail = true;
            $this->testEmailTo = $catchAllTo;
        }

    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event ): void
    {
        $message = $event->getMessage();

        if ($message instanceof Email) {
            $subject = $message->getHeaders()->get('subject')->getBodyAsString();
            if (preg_match('/#(\d+)/', $subject, $matches)) {
                $orderId = $matches[1];
            }
        }
        if ($message instanceof Email && $this->isTestEmail) {
            $emails = is_array($this->testEmailTo) ? [$this->testEmailTo] : explode(',', $this->testEmailTo);
            $isEmailsValid = $this->isValidEmail($emails);
            if (!$isEmailsValid) {
                throw new \Exception('Invalid email address in MAILER_CATCHALL_TO');
            }
            $storeDomain = null;
            if (!empty($orderId)) {
                $order = $this->orderRepository->findByOrderId($orderId);
                $storeDomain = $order?->getStoreDomain();
            }

            $storeName  = $this->storeInfoService->getStoreName($storeDomain);
            $salesEmail = $this->storeInfoService->getSalesEmail($storeDomain);
            //StoreConfigEnum::SALES_EMAIL;

            $message->from(new Address('test-' . StoreConfigEnum::SALES_EMAIL,  $storeName));


            $message->getHeaders()->remove('cc');

            $subject = '[TEST EMAIL] ' . $message->getHeaders()->get('subject')->getBodyAsString();
            $message->getHeaders()->remove('subject');
            $message->subject($subject);

            $toEmails = $message->getTo();
            $isEmailsWhitelisted = $this->isEmailsWhitelisted($toEmails);
            if (!$isEmailsWhitelisted) {
                // If email is not whitelisted, send it to the catch-all email
                $message->getHeaders()->remove('to');

                $firstAddress = $emails[0];
                $message->to($firstAddress);
                if (count($emails) > 1) {
                    $message->cc(...array_slice($emails, 1));
                }
            }
        }

    }

    private function isEmailsWhitelisted(array $emails): bool
    {
        $whitelistedEmails = array_merge($this->whiteListedDevsEmails, $this->whiteListedTesterEmails);

        foreach ($emails as $toEmail) {
            $emailAddress = $toEmail->getAddress();

            // Check if the email is directly in the whitelisted emails list
            if (in_array($emailAddress, $whitelistedEmails)) {
                continue;
            }

            // Check if the domain of the email is in the whitelisted domains
            $emailParts = explode('@', $emailAddress);
            if (count($emailParts) === 2 && in_array($emailParts[1], $this->whiteListedDomains)) {
                continue;
            }

            // If the email is not whitelisted by address or domain, return false
            return false;
        }

        // If all emails are either whitelisted directly or by domain, return true
        return true;
    }


    private function isValidEmail($email): bool
    {
        if (is_array($email)) {
            foreach ($email as $singleEmail) {
                if (!filter_var($singleEmail, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
            }
            return true;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

}