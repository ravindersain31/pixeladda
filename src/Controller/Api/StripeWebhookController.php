<?php

namespace App\Controller\Api;

use App\Service\Webhook\StripeWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    #[Route('/webhook/stripe-invoice', name: 'webhook_stripe_invoice', methods: ['POST'])]
    public function handleWebhook(Request $request, StripeWebhookService $stripeWebhookService): Response
    {
        try {
            $stripeWebhookService->handle($request);
            return new Response('Webhook processed', Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
