<?php

namespace App\Controller\Admin\Reports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\OrderRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;

#[Route('/history-reports', name: 'history_')]
class OrderHistoryController extends AbstractController
{
    #[Route('/', name: 'reports')]
    public function index(): Response
    {
        return $this->render('admin/reports/order_history/index.html.twig', [
            'view' => 'reports',
        ]);
    }

    #[Route('/sides', name: 'sides')]
    public function sides(): Response
    {
        return $this->render('admin/reports/order_history/index.html.twig', [
            'view' => 'sides',
        ]);
    }

    #[Route('/grommets', name: 'grommets')]
    public function grommets(): Response
    {
        return $this->render('admin/reports/order_history/index.html.twig', [
            'view' => 'grommets',
        ]);
    }

    #[Route('/imprint-color', name: 'imprint_color')]
    public function imprintColor(): Response
    {
        return $this->render('admin/reports/order_history/index.html.twig', [
            'view' => 'imprint_color',
        ]);
    }

    #[Route('/frames', name: 'frames')]
    public function frames(): Response
    {
        return $this->render('admin/reports/order_history/index.html.twig', [
            'view' => 'frames',
        ]);
    }

    #[Route('/referrals', name: 'referrals')]
    public function referrals(): Response
    {
        return $this->render('admin/reports/order_history/index.html.twig', [
            'view' => 'referrals',
        ]);
    }


    #[Route('/grommets/pdf', name: 'grommets_report_pdf')]
    public function grommetsReportPdf(
        Request $request,
        OrderRepository $orderRepository
    ): Response {

        $start = new \DateTime($request->query->get('start'));
        $end   = new \DateTime($request->query->get('end'));

        $orders = $orderRepository->filterOrderSelective(
            fromDate: \DateTimeImmutable::createFromMutable($start),
            endDate: \DateTimeImmutable::createFromMutable($end),
            result: true
        );

        $ordersByGrommets = [];
        $totalOrders = count($orders);
        $totalQuantity = 0;

        foreach ($orders as $order) {
            foreach ($order['orderItems'] ?? [] as $item) {
                if (empty($item['addOns']['grommets'])) continue;
                if ($item['addOns']['grommets']['key'] === 'NONE') continue;

                $key = $item['addOns']['grommets']['key'];

                $ordersByGrommets[$key]['totalQuantity']
                    = ($ordersByGrommets[$key]['totalQuantity'] ?? 0) + $item['quantity'];

                $ordersByGrommets[$key]['orderIds'][] = $item['orderId'];
                $ordersByGrommets[$key]['orderIds'] = array_unique($ordersByGrommets[$key]['orderIds']);
            }
        }

        foreach ($ordersByGrommets as $row) {
            $totalQuantity += $row['totalQuantity'];
        }

        $html = $this->renderView('emails/pdf/grommets_report.html.twig', [
            'start' => $start,
            'end' => $end,
            'ordersByGrommets' => $ordersByGrommets,
            'totalOrders' => $totalOrders,
            'totalQuantity' => $totalQuantity,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'grommets-report-'.$start->format('Y-m-d').'.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"'
            ]
        );
    }

}
