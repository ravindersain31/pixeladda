<?php

namespace App\Controller\Api;

use App\Service\Webhook\PayPalWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PayPalWebhookController extends AbstractController
{
    #[Route('/webhook/paypal-invoice', name: 'webhook_paypal_invoice', methods: ['POST'])]
    public function handleWebhook(Request $request, PayPalWebhookService $payPalWebhookService): Response
    {
        try {
            $payPalWebhookService->handle($request);
            return new Response('Webhook processed', Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
