<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Order\OrderController;
use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Service\Admin\DashboardWidgetService;
use App\Service\Admin\MarketingWidgetService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Admin\CouponRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{

    #[Route('/admin', name: 'ysp')]
    public function ysp(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        return $this->forward('App\Controller\Admin\Order\OrderController::index', [
            '_route' => 'orders',
            'status' => 'all',
            'page' => $page,
        ]);
    }

    #[Route('/', name: 'dashboard')]
    public function index(CouponRepository $couponRepository): Response
    {
        $couponSections = $couponRepository->getManuallyGroupedCoupons();
        return $this->render('admin/dashboard/index.html.twig', [
            'couponSections' => $couponSections,
        ]);
    }

    #[Route('/metrics', name: 'dashboard_metrics')]
    public function metrices(Request $request, DashboardWidgetService $widgetService, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        if ($this->isGranted(['ROLE_SUPER_ADMIN', 'dashboard'])) {
            return $this->render('admin/dashboard/metrics.html.twig', [
                'widgetGroup' => $widgetService->dashboardWidgetList(),
                'last7DaysData' => $widgetService->getLast7DaysData(),
            ]);
        }

        if ($this->isGranted(['ROLE_BLOG', 'dashboard'])) {
            return $this->forward('App\Controller\Admin\Blog\PostController::index', [
                '_route' => 'blog',
            ]);
        }

        if ($this->isGranted(['ROLE_MARKETING', 'dashboard'])) {
            return $this->forward('App\Controller\Admin\Marketing\DashboardController::index', [
                '_route' => 'marketing',
            ]);
        }

        if ($this->isGranted(['ROLE_WAREHOUSE', 'dashboard'])) {
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => 'P1']);
        }

        $page = $request->query->getInt('page', 1);

        return $this->forward('App\Controller\Admin\Order\OrderController::index', [
            '_route' => 'orders',
            'status' => 'all',
            'page' => $page,
        ]);

//        $query = $entityManager->getRepository(Order::class)->filterOrder(
//            status: [OrderStatusEnum::RECEIVED]
//        );
//        $page = $request->query->getInt('page', 1);
//        $orders = $paginator->paginate($query, $page, 20);
//        return $this->render('admin/dashboard/orders.html.twig', [
//            'orders' => $orders,
//        ]);
    }

    #[Route('/dashboard/widget-value/{widgetName}', name: 'dashboard_widget_value')]
    public function getWidgetValue(string $widgetName, DashboardWidgetService $widgetService): Response
    {
        if ($this->isGranted(['ROLE_SUPER_ADMIN', 'dashboard_widget_value'])) {
            $value = $widgetService->get($widgetName);
            return $this->json([
                'value' => $value,
            ]);
        }

        return $this->json([
            'value' => 0,
        ]);
    }

    #[Route('/dashboard-marketing/widget-value/{widgetName}', name: 'dashboard_marketing_widget_value')]
    public function getMarketingWidgetValue(string $widgetName, MarketingWidgetService $widgetService): Response
    {
        if ($this->isGranted(['ROLE_MARKETING', 'dashboard_marketing_widget_value'])) {
            $value = $widgetService->get($widgetName);
            return $this->json([
                'value' => $value,
            ]);
        }

        return $this->json([
            'value' => 0,
        ]);
    }

}
