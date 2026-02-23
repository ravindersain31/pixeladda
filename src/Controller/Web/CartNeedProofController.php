<?php

namespace App\Controller\Web;

use App\Entity\Cart;
use App\Form\NeedProofType;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartNeedProofController extends AbstractController
{
    #[Route('/cart/{cartId}/need-proof/update', name: 'cart_need_proof_update', methods: ['POST'])]
    public function updateNeedProof(
        string $cartId,
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $cart = $cartRepository->findOneBy(['cartId' => $cartId]);
        
        if (!$cart) {
            return $this->json(['success' => false, 'message' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(NeedProofType::class, $cart);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Cart $cart */
            $cart = $form->getData();

            if ($cart->isNeedProof()) {
                $cart->setDesignApproved(false);
            }
            
            $em->flush();

            return $this->json([
                'success' => true,
                'needProof' => $cart->isNeedProof(),
                'designApproved' => $cart->isDesignApproved(),
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Invalid form data',
            'errors' => (string) $form->getErrors(true)
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/cart/{cartId}/need-proof/approve', name: 'cart_need_proof_approve', methods: ['POST'])]
    public function approveDesign(
        string $cartId,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $cart = $cartRepository->findOneBy(['cartId' => $cartId]);
        
        if (!$cart) {
            return $this->json(['success' => false, 'message' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        $cart->setDesignApproved(true);
        $cart->setNeedProof(false);
        
        $em->flush();

        return $this->json([
            'success' => true,
            'needProof' => $cart->isNeedProof(),
            'designApproved' => $cart->isDesignApproved(),
        ]);
    }
}