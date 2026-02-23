<?php

namespace App\Controller\Cron;

use App\Helper\Timer;
use App\Service\CleanerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CleanerController extends AbstractController
{
    #[Route(path: '/cleaner/all', name: 'cron_cleaner_all')]
    public function index(CleanerService $cleanerService, Timer $timer): Response
    {
        $report = [];

        $timer->start('deleteExpiredEmailQuote');
        $deleteExpiredEmailQuote = $cleanerService->deleteExpiredEmailQuote();
        $report['deleteExpiredEmailQuote'] = [
            'response' => $deleteExpiredEmailQuote,
            'executionTime' => $timer->end('deleteExpiredEmailQuote')
        ];

        $timer->start('deleteExpiredSavedDesign');
        $deleteExpiredSavedDesign = $cleanerService->deleteExpiredSavedDesign();
        $report['deleteExpiredSavedDesign'] = [
            'response' => $deleteExpiredSavedDesign,
            'executionTime' => $timer->end('deleteExpiredSavedDesign')
        ];

        $timer->start('deleteExpiredSavedCart');
        $deleteExpiredSavedCart = $cleanerService->deleteExpiredSavedCart();
        $report['deleteExpiredSavedCart'] = [
            'response' => $deleteExpiredSavedCart,
            'executionTime' => $timer->end('deleteExpiredSavedCart')
        ];

        $timer->start('deleteCartWithZeroTotal');
        $deleteCartWithZeroTotal = $cleanerService->deleteCartWithZeroTotal();
        $report['deleteCartWithZeroTotal'] = [
            'response' => $deleteCartWithZeroTotal,
            'executionTime' => $timer->end('deleteCartWithZeroTotal')
        ];

        $timer->start('deleteCartNotAssociatedToOrder');
        $deleteCartNotAssociatedToOrder = $cleanerService->deleteCartNotAssociatedToOrder();
        $report['deleteCartNotAssociatedToOrder'] = [
            'response' => $deleteCartNotAssociatedToOrder,
            'executionTime' => $timer->end('deleteCartNotAssociatedToOrder')
        ];

        return $this->json(['status' => 'ok', 'report' => $report]);
    }
}