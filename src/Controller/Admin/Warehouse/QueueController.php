<?php

namespace App\Controller\Admin\Warehouse;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderLog;
use App\Entity\Admin\WarehouseShipByList;
use App\Entity\Order;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\ShippingEnum;
use App\Enum\ShippingStatusEnum;
use App\Repository\Admin\WarehouseOrderRepository;
use App\Repository\OrderRepository;
use App\Service\OrderLogger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use App\Mercure\TokenProvider;
use App\Repository\OrderShipmentRepository;
use App\Service\Admin\WarehouseService;

#[Route('/warehouse/queue')]
class QueueController extends AbstractController
{

    #[Route('/printer/{printer}', name: 'warehouse_queue_by_printer', priority: -1)]
    public function index(string $printer, Request $request, EntityManagerInterface $entityManager, WarehouseOrderRepository $orderRepository, SerializerInterface $serializer): Response
    {

        $this->denyAccessUnlessGranted($request->get('_route'));

        $search = $request->get('wq', null);
        $searchShipBy = $request->get('shipBy', null);

        $lists = $entityManager->getRepository(WarehouseShipByList::class)->findActiveListByPrinter($printer);
        $printerOrders = $orderRepository->findQueue(printerName: $printer)->getResult();

        $ordersShipBy = $this->groupOrdersShipBy($printerOrders);

        $dateRange = ['', ''];

        if ($searchShipBy) {
            try {
                $shipBy = new \DateTimeImmutable($searchShipBy);
                $formattedDate = $shipBy->format('Y-m-d');
                $dateRange = [$formattedDate, $formattedDate];
            } catch (\Exception $e) {
                
            }
        }

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'printer' => $printer,
            'filters' => [
                'orderId' => $search,
                'dateRange' => $dateRange
            ],
            'lists2' => json_decode($serializer->serialize($lists, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
            'ordersShipBy2' => json_decode($serializer->serialize($ordersShipBy, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
            'fullScreen' => true,
        ]);
    }

    #[Route('/unassigned', name: 'warehouse_queue_unassigned')]
    public function unassigned(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->filterOrder(
            status: [OrderStatusEnum::SENT_FOR_PRODUCTION],
            orderByField: 'deliveryDate',
        );

        $ordersGroupedByDeliveryDate = $this->makeOrderGroupedByDate($query->getResult());

        $orders = $paginator->paginate($ordersGroupedByDeliveryDate, $page, 200);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
            'ordersGroupedByDeliveryDate' => array_reverse($ordersGroupedByDeliveryDate),
        ]);
    }

    #[Route('/create-shipment', name: 'warehouse_queue_create_shipment')]
    public function createShipment(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            hasShipping: 'no',
        );

        $orders = $paginator->paginate($query, $page, 50);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders-in-deleted-ship-by', name: 'warehouse_queue_order_in_deleted_ship_by')]
    public function orderInDeletedShipBy(Request $request, WarehouseOrderRepository $warehouseOrderRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $warehouseOrderRepository->findOrderInDeletedShipBy();

        $orders = $paginator->paginate($query, $page, 20);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders-in-deleted-ship-by/mark-done/{id}', name: 'warehouse_queue_order_in_deleted_ship_by_mark_done')]
    public function orderInDeletedShipByMarkDone(WarehouseOrder $warehouseOrder, Request $request, WarehouseOrderRepository $warehouseOrderRepository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);

        $warehouseOrderLog = new WarehouseOrderLog();
        $warehouseOrderLog->setOrder($warehouseOrder);
        $warehouseOrderLog->setLoggedBy($this->getUser());
        $warehouseOrderLog->setContent('Order has been marked as <b>Done</b> from deleted ship by list');
        $warehouseOrderLog->setCreatedAt(new \DateTimeImmutable());

        $warehouseOrder->addWarehouseOrderLog($warehouseOrderLog);
        $warehouseOrderRepository->save($warehouseOrder, true);

        $this->addFlash('success', 'Order has been marked as done successfully.');
        return $this->redirectToRoute('admin_warehouse_queue_order_in_deleted_ship_by');
    }

    #[Route('/ready-for-shipping-easy', name: 'warehouse_queue_ready_shipping_easy')]
    public function enteredInReadySE(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->filterOrder(
            status: [OrderStatusEnum::SENT_FOR_PRODUCTION],
            orderByField: 'deliveryDate',
        );

        $orders = $paginator->paginate($query, $page, 100);

        $ordersGroupedByDeliveryDate = $this->makeOrderGroupedByDate($orders->getItems());

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
            'ordersGroupedByDeliveryDate' => array_reverse($ordersGroupedByDeliveryDate),
        ]);
    }

    #[Route('/entered-into-shipping-easy', name: 'warehouse_queue_shipping_easy')]
    public function enteredInSE(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            orderByField: 'deliveryDate',
            orderBy: 'DESC',
        );

        $orders = $paginator->paginate($query, $page, 100);

        $ordersGroupedByDeliveryDate = $this->makeOrderGroupedByDate($orders->getItems());

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
            'ordersGroupedByDeliveryDate' => array_reverse($ordersGroupedByDeliveryDate),
        ]);
    }

    #[Route('/label-printed', name: 'warehouse_queue_label_printed')]
    public function labelPrinted(Request $request, WarehouseOrderRepository $orderRepository, OrderShipmentRepository $orderShipmentRepository, PaginatorInterface $paginator): Response
    {

        $page = $request->query->getInt('page', 1);

        $shipByFrom = $request->query->get('shipByFrom', null);
        $shipByTo = $request->query->get('shipByTo', null);

        $query = $orderRepository->getOrdersWithPreTransitStatus($shipByFrom, $shipByTo);

        $orders = $paginator->paginate($query, $page, 50);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/done', name: 'warehouse_queue_done')]
    public function doneOrders(Request $request, WarehouseOrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->addFlash('danger', 'This page is not available');
        return $this->redirectToRoute('admin_warehouse_queue_ready_to_print');

        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->findDoneOrders();

        $orders = $paginator->paginate($query, $page, 50);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/ready-to-ship', name: 'warehouse_queue_ready_to_ship')]
    public function readyToShip(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            hasShipping: 'yes',
        );

        $orders = $paginator->paginate($query, $page, 50);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/ready-to-print', name: 'warehouse_queue_ready_to_print')]
    public function markShipped(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
    {
        $this->addFlash('primary', 'This page is not available');
        return $this->redirectToRoute('admin_warehouse_queue_ready_to_ship');

        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $query = $orderRepository->filterOrder(
            status: [OrderStatusEnum::READY_FOR_SHIPMENT],
            hasShipping: 'yes',
        );

        $orders = $paginator->paginate($query, $page, 70);

        return $this->render('admin/warehouse/queue/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/move-ready-to-print/{wid}', name: 'warehouse_queue_move_ready_to_print')]
    public function moveReadyToPrintOrder(string $wid, Request $request, WarehouseOrderRepository $warehouseOrderRepository, OrderRepository $orderRepository, OrderLogger $orderLogger, WarehouseService $warehouseService): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $warehouseOrder = $warehouseOrderRepository->find($wid);
        if (!$warehouseOrder) {
            $this->addFlash('danger', 'Order not found.');
            return $this->redirectToRoute('warehouse_queue_ready_to_print');
        }

        $userOrder = $warehouseOrder->getOrder();

        $userOrder->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
        $userOrder->setShippingStatus(ShippingStatusEnum::LABEL_PURCHASED);

        $userOrder->setUpdatedAt(new \DateTimeImmutable());
        $orderRepository->save($userOrder, true);

        $orderLogger->setOrder($userOrder);
        $orderLogger->log('The order has been marked as <b>Done</b> in the Order Queue and moved into the <b>Ready to Ship</b> order tab for a further shipment process.');

        $warehouseOrder->setUpdatedAt(new \DateTimeImmutable());
        $warehouseOrderRepository->save($warehouseOrder, true);

        $this->addFlash('success', 'Order #' . $userOrder->getOrderId() . ' has been moved into the <b>Ready to Print</b> order tab for a further shipment process.');
        return $this->redirectToRoute('admin_warehouse_queue_ready_to_print');
    }


    #[Route('/mark-done/{wid}/ready-to-ship', name: 'warehouse_queue_mark_done_and_ready_to_ship')]
    public function markDoneAndReadyToShipOrder(string $wid, Request $request, WarehouseOrderRepository $warehouseOrderRepository, OrderRepository $orderRepository, OrderLogger $orderLogger): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $warehouseOrder = $warehouseOrderRepository->find($wid);
        if (!$warehouseOrder) {
            $this->addFlash('danger', 'Order not found.');
            return $this->redirectToRoute('warehouse_queue_done');
        }
        $userOrder = $warehouseOrder->getOrder();

        if($userOrder->getShippingStatus() !== ShippingStatusEnum::LABEL_PURCHASED) {
            if ($userOrder->getParent() && $userOrder->isSplitOrder()) {
                $userOrder->setStatus(OrderStatusEnum::COMPLETED);
                $userOrder->setShippingStatus(ShippingStatusEnum::SHIPPED);
            } else {
                $userOrder->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
                $userOrder->setShippingStatus(ShippingStatusEnum::READY_FOR_SHIPMENT);
            }
        }

        if ($userOrder->getParent() && $userOrder->isSplitOrder() && $this->checkIfSubOrdersAreReady($warehouseOrder)) {
            $userOrder->getParent()->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
            $orderRepository->save($userOrder->getParent(), true);
        }

        $userOrder->setShippingMethod(ShippingEnum::EASYPOST);
        $userOrder->setUpdatedAt(new \DateTimeImmutable());
        $orderRepository->save($userOrder, true);

        $orderLogger->setOrder($userOrder);
        $orderLogger->log('The order has been marked as <b>Done</b> in the Order Queue and moved into the <b>Ready to Ship</b> order tab for a further shipment process.');

        $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
        $warehouseOrder->setUpdatedAt(new \DateTimeImmutable());
        $warehouseOrderRepository->save($warehouseOrder, true);

        $this->addFlash('success', 'Order #' . $userOrder->getOrderId() . ' has been marked as done successfully and moved into the <b>Ready to Ship</b> order tab for a further shipment process.');
        return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $warehouseOrder->getPrinterName()]);
    }

    #[Route('/mark-done/{wid}', name: 'warehouse_queue_mark_done')]
    public function markDoneOrder(string $wid, Request $request, WarehouseOrderRepository $warehouseOrderRepository, OrderRepository $orderRepository, OrderLogger $orderLogger): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $warehouseOrder = $warehouseOrderRepository->find($wid);
        if (!$warehouseOrder) {
            $this->addFlash('danger', 'Order not found.');
            return $this->redirectToRoute('warehouse_queue_done');
        }
        $userOrder = $warehouseOrder->getOrder();

        $userOrder->setStatus(OrderStatusEnum::COMPLETED);
        $userOrder->setUpdatedAt(new \DateTimeImmutable());
        $orderRepository->save($userOrder, true);

        $orderLogger->setOrder($userOrder);
        $orderLogger->log('The order has been marked as <b>Done</b> in the Order Queue and moved into the Completed orders.');

        $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
        $warehouseOrderRepository->save($warehouseOrder, true);

        if ($userOrder->getParent() && $userOrder->isSplitOrder() && $this->checkIfSubOrdersAreReady($warehouseOrder)) {
            $userOrder->getParent()->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
            $orderRepository->save($userOrder->getParent(), true);
        }

        $this->addFlash('success', 'Order #' . $userOrder->getOrderId() . ' has been marked as done successfully.');
        return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $warehouseOrder->getPrinterName()]);
    }

    public function checkIfSubOrdersAreReady($warehouseOrder): bool
    {
        $subOrders = $warehouseOrder->getOrder()->getParent()->getSubOrders();

        foreach ($subOrders as $subOrder) {
            if ($subOrder->getWarehouseOrder()->getOrder()->getStatus() !== OrderStatusEnum::COMPLETED) {
                return false;
            }
        }
        return true;
    }

    public function filterListByDate(array $lists, array $ordersShipBy): array
    {
        // Extract the date keys from the ordersShipBy array
        $filterDates = array_keys($ordersShipBy);

        // Convert the dates to an associative array for O(1) lookup
        $filterDatesSet = [];
        foreach ($filterDates as $date) {
            $filterDatesSet[$date] = true;
        }

        // Filter the lists based on whether their shipBy date is in the set
        $filtered = array_filter($lists, function ($list) use ($filterDatesSet) {
            $shipByDate = $list->getShipBy()->format('Y-m-d');
            return isset($filterDatesSet[$shipByDate]);
        });

        // Re-index the result array
        return array_values($filtered);
    }


    #[Route('/search', name: 'warehouse_queue_search')]
    public function searchOrder(Request $request, WarehouseOrderRepository $orderRepository, OrderRepository $orderRepo): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $search = $request->query->get('wq');
        if (empty($search)) {
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => 'P1']);
        }
        $orders = $orderRepository->searchOrder($search)->getResult();
        $printerNames = array_values(array_filter(array_unique(array_map(fn($order) => $order['printerName'], $orders))));
        $firstPrinter = $printerNames[0] ?? null;
        if ($firstPrinter && count($printerNames) > 1) {
            $this->addFlash('warning', 'Orders are found in multiple printers. Redirecting to orders from first printer in the list.');
        }
        if (count($printerNames) <= 0) {
            $this->addFlash('info', 'No orders found for the search term: <b>' . $search . '</b>');
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => 'P1']);
        }

        $order = $orderRepo->findOneBy(['orderId' => $search]);
        $shipBy = null;

        if ($order && count($orders) === 1) {
            $shipBy = $orderRepository->findOneBy(['order' => $order])?->getShipBy()?->format('Y-m-d');
        }

        $params = ['printer' => $firstPrinter];

        if ($order || $search) {
            $params['wq'] = $search;
        }
        if ($shipBy) {
            $params['shipBy'] = $shipBy;
        }

        return $this->redirectToRoute('admin_warehouse_queue_by_printer', $params);

    }

    #[Route('/delete-ship-by-list/{id}', name: 'warehouse_queue_delete_ship_by_list')]
    public function deleteShipByList(WarehouseShipByList $list, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        if ($list->getShipBy()) {
            $ordersInList = $entityManager->getRepository(WarehouseOrder::class)->findQueue(
                printerName: $list->getPrinterName(),
                onlyCount: true,
                shipBy: $list->getShipBy()
            )->getOneOrNullResult();
            $ordersInList = $ordersInList['totalOrders'] ?? 0;
        } else {
            $ordersInList = 0;
        }
        if ($ordersInList > 0) {
            $this->addFlash('danger', 'Ship by list cannot be deleted because it has orders. Please move orders first to other ship by list to delete this list.');
        } else {
            $list->setDeletedAt(new \DateTimeImmutable());
            $entityManager->persist($list);
            $entityManager->flush();
            $this->addFlash('success', 'Ship by list has been deleted successfully.');
        }
        return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $list->getPrinterName()]);
    }

    private function groupOrdersShipBy(array $orders): array
    {
        $groupedOrders = [];

        foreach ($orders as $order) {
            $dateKey = $order->getShipBy()->format('Y-m-d');
            if (!isset($groupedOrders[$dateKey])) {
                $groupedOrders[$dateKey] = [];
            }
            $groupedOrders[$dateKey][] = $order;
        }

        return array_reverse($groupedOrders);
    }

    private function makeOrderGroupedByDate(array $orders): array
    {
        $groupedOrders = [];

        foreach ($orders as $order) {
            $warehouseOrder = $order;
            $userOrder = $order;
            if ($userOrder instanceof WarehouseOrder) {
                $userOrder = $order->getOrder();
            } else {
                $warehouseOrder = $order->getWarehouseOrder();
            }
            if (!$warehouseOrder || !$userOrder) {
                continue;
            }
            if (
                $warehouseOrder->getPrintStatus() === WarehouseOrderStatusEnum::DONE || (
                    $warehouseOrder->getShipBy() !== null &&
                    $userOrder->getPrinterName() !== null
                )
            ) {
                continue;
            }
            $dateKey = $userOrder->getDeliveryDate()->format('Y-m-d');
            if (!isset($groupedOrders[$dateKey])) {
                $groupedOrders[$dateKey] = [];
            }
            $groupedOrders[$dateKey][] = $order;
        }

        return array_reverse($groupedOrders);
    }
}
