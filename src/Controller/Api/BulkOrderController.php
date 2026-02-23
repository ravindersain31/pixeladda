<?php

namespace App\Controller\Api;

use App\Helper\RecaptchaValidatorHelper;
use App\Service\BulkOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class BulkOrderController extends AbstractController
{
    public const MAX_ATTEMPTS = 10;

    public function __construct(
        private readonly BulkOrderService           $bulkOrderService,
        private readonly RecaptchaValidatorHelper   $recaptchaValidatorHelper
    ) {}

    #[Route('/create-bulk-order', name: 'api_create_bulk_order', methods: ['POST'])]
    public function create(Request $request, Session $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'])) {
            return $this->json(['error' => 'Email is required'], 400);
        }

        // $bulkOrderAttempts = $data['attempts'] ?? self::MAX_ATTEMPTS + 1;
        // if($bulkOrderAttempts > self::MAX_ATTEMPTS) {
        $recaptchaToken = $data['recaptchaToken'] ?? null;
        if (!$recaptchaToken || !$this->recaptchaValidatorHelper->validate($recaptchaToken)) {
            return new JsonResponse(['error' => "Please click on I'm not a Robot"], 400);
        }
        // }

        try {
            $this->bulkOrderService->createBulkOrder(
                email: $data['email'],
                firstName: $data['firstName'] ?? null,
                lastName: $data['lastName'] ?? null,
                phoneNumber: $data['phoneNumber'] ?? null,
                company: $data['company'] ?? null,
                quantity: isset($data['quantity']) ? (int)$data['quantity'] : null,
                budget: $data['budget'] ?? null,
                deliveryDate: !empty($data['deliveryDate']) ? new \DateTimeImmutable($data['deliveryDate']) : null,
                productInInterested: $data['productInInterested'] ?? null,
                comment: $data['comment'] ?? null,
                store: $data['store'] ?? 1,
            );

            return $this->json([
                'success' => true,
                'message' => 'Your request has been successfully submitted. We will contact you within one hour with more information.'
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/price-create-bulk-order', name: 'api_price_create_bulk_order', methods: ['POST'])]
    public function createBulkOrder(Request $request, Session $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $bulk = $data['bulk_order'];

        if (!$data || empty($bulk['email'])) {
            return $this->json(['error' => 'Email is required'], 400);
        }

        $recaptchaToken = $bulk['recaptcha[token]'] ?? null;
        if ($recaptchaToken) {
            if (!$this->recaptchaValidatorHelper->validate($recaptchaToken)) {
                return new JsonResponse(['error' => "Please click on I'm not a Robot"], 400);
            }
        }

        try {
            $this->bulkOrderService->createBulkOrder(
                email: $bulk['email'],
                firstName: $bulk['firstName'] ?? null,
                lastName: $bulk['lastName'] ?? null,
                phoneNumber: $bulk['phoneNumber'] ?? null,
                company: $bulk['company'] ?? null,
                quantity: isset($bulk['quantity']) ? (int) $bulk['quantity'] : null,
                budget: $bulk['budget'] ?? null,
                deliveryDate: !empty($bulk['deliveryDate']) ? new \DateTimeImmutable($bulk['deliveryDate']) : null,
                productInInterested: $bulk['productInInterested'] ?? null,
                comment: $bulk['comment'] ?? null,
                store: $bulk['store'] ?? 1,
            );


            return $this->json([
                'success' => true,
                'message' => 'Your request has been successfully submitted. We will contact you within one hour with more information.'
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
