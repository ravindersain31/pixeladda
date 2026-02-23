<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\CartManagerService;
use App\Service\SubscriberService;
use App\Trait\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class EditorController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/repeat-order', name: 'editor_repeat_order', methods: ['POST'])]
    public function artwork(Request $request, OrderRepository $repository, CartManagerService $cartManagerService): Response
    {
        $orderId = $request->get('orderId');

        $order = $repository->findOneBy(['orderId' => $orderId]);
        if (!$order instanceof Order) {
            return $this->json([
                'success' => false,
                'message' => 'Order does not exist. Please try again or call +1-877-958-1499 for assistance.',
            ]);
        }

        $cart = $cartManagerService->deepClone($order->getCart());

        return $this->json([
            'success' => true,
            'orderId' => $orderId,
            'redirect' => $this->generateUrl('cart', ['id' => $cart->getCartId()]),
        ]);
    }

    #[Route(path: '/subscribe', name: 'editor_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, SubscriberService $subscriberService): Response 
    {
        $email = $request->get('email') ?? null;
        $storeId = $this->getStore()?->id;

        if (!$email || !$storeId) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid email address or store context.',
            ], 400);
        }

        try {
            $subscriberService->subscribe(
                email: $email,
                type: SubscriberService::ENQUIRY_SAVE_OFFER,
                offers: true,
                store: $storeId,
            );

            return $this->json([
                'success' => true,
                'message' => 'Subscribed successfully.',
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Subscription failed. ' . $e->getMessage(),
            ], 500);
        }
    }
}