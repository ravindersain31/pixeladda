<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\Cart\AbandonedCart;
use App\Twig\LightCartProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('/save-cart-email', name: 'api_save_cart_email', methods: ['POST'])]
    public function saveAbandonedCart(Request $request, EntityManagerInterface $entityManager, LightCartProvider $lightCartProvider): Response
    {
        try {

            $email = $request->request->get('email') ?? null;
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Invalid email address.'], Response::HTTP_BAD_REQUEST);
            }

            $cartId = $lightCartProvider->getCartId();
            $cart = $entityManager->getRepository(Cart::class)->findOneBy(['cartId' => $cartId]);
            
            if (!$cart) {
                return $this->json(['error' => 'Cart not found.'], Response::HTTP_NOT_FOUND);
            }
            
            $abandonedCart = $entityManager->getRepository(AbandonedCart::class)->findOneBy(['cart' => $cart]);

            if ($abandonedCart) {
                if ($abandonedCart->getEmail() === $email) {
                    return $this->json(['message' => 'Cart already saved with this email.'], Response::HTTP_OK);
                }
                $abandonedCart->setEmail($email);
            } else {
                $abandonedCart = new AbandonedCart();
                $abandonedCart->setCart($cart);
                $abandonedCart->setEmail($email);
                $entityManager->persist($abandonedCart);
            }

            $entityManager->flush();

            return $this->json(['message' => 'Cart email saved successfully.'], Response::HTTP_OK);

        }catch (\Exception $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
