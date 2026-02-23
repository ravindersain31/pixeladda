<?php

namespace App\Controller\Cron;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PushToShippingEasyController extends AbstractController
{
    #[Route(path: '/push-to-shipping-easy', name: 'cron_push_to_shipping_easy')]
    public function index(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }
}