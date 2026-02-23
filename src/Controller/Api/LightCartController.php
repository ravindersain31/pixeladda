<?php

namespace App\Controller\Api;

use App\Helper\LightCartHelper;
use App\Service\CartManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class LightCartController extends AbstractController
{
    #[Route('/light-cart', name: 'api_light_cart', methods: ['GET'], priority: 1)]
    public function index(SerializerInterface $serializer, LightCartHelper $lightCartHelper): Response
    {
        try{
            return new Response($serializer->serialize($lightCartHelper->build(), 'json', ['groups' => 'apiData']));
        }catch (\Exception $e) {
            return new Response($serializer->serialize(['message' => $e->getMessage()], 'json', ['groups' => 'apiData']));
        }
    }

    #[Route(path: '/remove-from-cart', name: 'api_remove_item_from_cart', methods: ['POST'])]
    public function removeItem(Request $request, CartManagerService $cartManagerService): Response
    {
        try{
            $cartId = $request->get('cartId');
            $itemId = $request->get('itemId');
            $cart = $cartManagerService->getCart($cartId);
            $cart->setInternationalShippingCharge(false);
            $cart->setInternationalShippingChargeAmount(0);
            if(!$cartId || !$itemId) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cart Id or Item Id not found',
                ]);
            }

            $cart = $cartManagerService->removeItem($cartId, $itemId);

            return $this->json([
                'success' => true,
                'message' => 'Item removed successfully',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
