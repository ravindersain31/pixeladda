<?php

namespace App\Controller\Api\MyAccount;

use App\Entity\AppUser;
use App\Service\SavedPaymentDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/my-account/saved-cards')]
class SavedCardsController extends AbstractController
{
    public function __construct(
        private readonly SavedPaymentDetailService $savedPaymentDetailService,
    ) {}

    #[Route('/add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        /** @var AppUser|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['nonce'])) {
            return $this->json(['error' => 'Missing payment nonce'], 400);
        }

        try {
            $result = $this->savedPaymentDetailService->add($user, $data['nonce']);
            
            if (!$result['success']) {
                return $this->json([
                    'success' => false,
                    'error'   => $result['error']['message'],
                    'code'    => $result['error']['code'],
                ], 400);
            }

            return $this->json(['success' => true]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Failed to save card',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $result = $this->savedPaymentDetailService->deleteById($user, $id);

        if (!$result['success']) {
            return $this->json([
                'success' => false,
                'error'   => $result['error'],
                'details'=> $result['details'] ?? null,
            ], 400);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/default', methods: ['POST'])]
    public function setDefault(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->savedPaymentDetailService->setDefaultById($user, $id);
            return $this->json(['success' => true]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Failed to set default card',
                'details'=> $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/my-account-list', name: 'my_account_card_list', methods: ['GET'])]
    public function list(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $savedCards = $this->savedPaymentDetailService->getSavedPaymentDetails($user);

        return $this->render('customer/saved-cards/_list.html.twig', [
            'savedCards' => $savedCards
        ]);
    }

    #[Route('/card-list', name: 'cards_list', methods: ['GET'])]
    public function cardList(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $savedCards = $this->savedPaymentDetailService->getSavedPaymentDetails($user);

        return $this->render('/checkout/saved-cards/_list.html.twig', [
            'savedCards' => $savedCards
        ]);
    }

    #[Route('/auth-check', methods: ['GET'])]
    public function authCheck(): JsonResponse
    {
        return $this->json([
            'authenticated' => (bool) $this->getUser(),
        ]);
    }
}
