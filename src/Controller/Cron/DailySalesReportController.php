<?php

namespace App\Controller\Cron;

use App\Entity\Store;
use App\Service\CogsHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DailySalesReportController extends AbstractController
{
    #[Route(path: '/daily-sales-report', name: 'cron_daily_sales_report')]
    public function index(Request $request, EntityManagerInterface $entityManager, CogsHandlerService $cogs): Response
    {
        $date = new \DateTime();
        $date->modify('-1 day');
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }
        $store = $entityManager->getRepository(Store::class)->findOneBy(['shortName' => 'YSP']);
        $dailyCog = $cogs->getDailyCog($date, $store);

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }
}