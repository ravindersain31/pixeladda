<?php

namespace App\Controller\Web\MyAccount;

use App\Entity\EmailReview;
use App\Entity\Order;
use App\Form\EmailReviewType;
use App\Service\CartManagerService;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route(path: '/order/{oid}', name: 'order_view')]
    public function orderView(string $oid, Request $request, EntityManagerInterface $entityManager): Response
    {

        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $oid]);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $emailReviewToken = $request->get('review');

        $emailReviewForm = $this->createForm(EmailReviewType::class);
        $emailReviewForm->handleRequest($request);
        if ($emailReviewForm->isSubmitted() && $emailReviewForm->isValid()) {
            $data = $emailReviewForm->getData();
            $review = new EmailReview();
            $review->setOrder($order);
            $review->setUserIp($request->getClientIp());
            $review->setName($data['name']);
            $review->setRating($data['rating']);
            $review->setComment($data['comments']);

            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Thank you for your review!');

            return $this->redirectToRoute('order_view', [
                'oid' => $oid,
            ]);
        }

        return $this->render('account/order/view.html.twig', [
            'order' => $order,
            'emailReviewToken' => $emailReviewToken,
            'emailReviewForm' => $emailReviewForm->createView(),
        ]);
    }

    #[Route(path: '/customer-repeat-order/{oId}', name: 'customer_repeat_order')]
    public function repeatOrder($oId, EntityManagerInterface $entityManager, CartManagerService $cartManagerService): Response
    {
        try {
            $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $oId]);
            if (!$order->getCart()) {
                $this->addFlash('danger', 'Cart not found');
                return $this->redirectToRoute('order_history');
            }
            $cart = $cartManagerService->deepClone($order->getCart(), isRepeatOrder: true, order: $order);
            return $this->redirectToRoute('cart', [
                'id' => $cart->getCartId(),
            ]);

        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('order_history');
        }
    }

}
