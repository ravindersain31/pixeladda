<?php

namespace App\Controller\Cron;

use App\Entity\Cart;
use App\Entity\Cart\AbandonedCart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Enum\StoreConfigEnum;
use App\Service\StoreInfoService;
use Symfony\Component\Mailer\MailerInterface;

class AbandonedCartController extends AbstractController
{
    public function __construct(
        private readonly StoreInfoService       $storeInfoService,
    ) {}
    #[Route(path: '/abandoned-cart', name: 'cron_abandoned_cart')]
    public function index(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        // clean up abandoned carts with orders
        $entityManager->getRepository(AbandonedCart::class)->removeAbandonedCartsWithOrders();

        // clean up abandoned carts older than 7 days
        $entityManager->getRepository(AbandonedCart::class)->removeAbandonedCartsOlderThan2Days();

        $abandonedCarts = $entityManager->getRepository(AbandonedCart::class)->findAbandonedCarts($date);

        /** @var AbandonedCart $abandonedCart */
        foreach ($abandonedCarts as $abandonedCart) {
            if (empty($abandonedCart) || !$abandonedCart->getEmail() || $abandonedCart->getCart()->getCartItems()->count() === 0) {
                $entityManager->remove($abandonedCart);
                continue;
            }
            $storeName = $this->storeInfoService->getStoreName();
            $email = new TemplatedEmail();
            $email->from(new Address(StoreConfigEnum::SALES_EMAIL, $storeName));
            $email->subject("Did You Forget Something? Come Back For Special Pricing!");
            $email->to($abandonedCart->getEmail()); // Assuming getEmail() method is used for the recipient
            $email->htmlTemplate('emails/cart_abandoned.html.twig')->context([
                'cart' => $abandonedCart->getCart()
            ]);
            $mailer->send($email);

            $abandonedCart->setNotifiedAt(new \DateTimeImmutable());
            $entityManager->persist($abandonedCart);
        }

        $entityManager->flush();
        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }
}