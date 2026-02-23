<?php

namespace App\Controller\Admin\Marketing;

use App\Service\Admin\MarketingWidgetService;
use App\Service\Admin\DashboardWidgetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/marketing', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'marketing')]
    public function index(Request $request, MarketingWidgetService $widgetService, DashboardWidgetService $dashboardWidgetService): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        return $this->render('admin/marketing/index.html.twig', [
            'widgetGroup' => $widgetService->dashboardWidgetList(),
            'last7DaysData' => $dashboardWidgetService->getLast7DaysData(),
        ]);
    }
}
