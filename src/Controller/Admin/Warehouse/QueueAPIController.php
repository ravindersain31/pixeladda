<?php

namespace App\Controller\Admin\Warehouse;

use App\Entity\Order;
use App\Enum\ShippingEnum;
use App\Service\OrderLogger;
use App\Enum\OrderStatusEnum;
use App\Repository\OrderRepository;
use App\Entity\Admin\WarehouseOrder;
use App\Service\Admin\WarehouseEvents;
use App\Service\MercureEventPublisher;
use App\Entity\Admin\WarehouseOrderLog;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Admin\WarehouseShipByList;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\Admin\WarehouseMercureEventEnum;
use App\Enum\OrderTagsEnum;
use App\Enum\ShippingStatusEnum;
use App\Helper\VichS3Helper;
use App\Mercure\TokenProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Admin\WarehouseOrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/warehouse/queue-api')]
class QueueAPIController extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseService $warehouseService,
        private readonly SerializerInterface $serializer,
        public readonly EventDispatcherInterface $eventDispatcher,
        private readonly MercureEventPublisher $mercurePublisher,
        private readonly WarehouseEvents $warehouseEvents,
        private readonly HttpClientInterface $httpClient
    ) {}

    #[Route('/printer/{printer}', name: 'warehouse_queue_api_by_printer', priority: -1)]
    public function index(string $printer, Request $request, EntityManagerInterface $entityManager, WarehouseOrderRepository $orderRepository): Response
    {
        $lists = $entityManager->getRepository(WarehouseShipByList::class)->findActiveListByPrinter($printer);
        $printerOrders = $orderRepository->findQueue(printerName: $printer)->getResult();
        $ordersShipBy = $this->groupOrdersShipBy($printerOrders);

        $shipByLists = [];
        foreach ($lists as $list) {
            $shipByDate = $list->getShipBy()?->format('Y-m-d') ?? null;
            $shipByLists[] = [
                'list' => $list,
                'orders' => $ordersShipBy[$shipByDate] ?? [],
            ];
        }

        return $this->json([
            'lists' => $shipByLists,
        ]);
    }

    private function groupOrdersShipBy(array $orders): array
    {
        $groupedOrders = [];

        foreach ($orders as $order) {
            $dateKey = $order->getShipBy()->format('Y-m-d');
            if (!isset($groupedOrders[$dateKey])) {
                $groupedOrders[$dateKey] = [];
            }
            $groupedOrders[$dateKey][] = [
                'id' => $order->getId(),
                'printed' => $order->getPrinted(),
                'notes' => $order->getNotes(),
                'comments' => $order->getComments(),
                'printStatus' => WarehouseOrderStatusEnum::getLabel($order->getPrintStatus(), true),
            ];
        }

        return array_reverse($groupedOrders);
    }

    #[Route('/warehouse-orders/logs', name: 'warehouse_order_logs', methods: ['GET'])]
    public function warehouseOrderLogs(Request $request): JsonResponse
    {
        $orderId = $request->query->get('id');
        if (!$orderId) {
            return new JsonResponse(['error' => 'Missing warehouse order ID'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($orderId);
        if (!$warehouseOrder) {
            return new JsonResponse(['error' => 'Warehouse Order not found'], 404);
        }

        $logs = $warehouseOrder->getWarehouseOrderLogs();

        if ($logs->isEmpty()) {
            return new JsonResponse(['error' => 'No logs found'], Response::HTTP_OK);
        }

        return $this->json([
            'warehouseOrderLogs' => $logs,
        ], 200, [], ['groups' => 'apiData']);
    }

    #[Route('/orders/update-sort', name: 'update_sort', methods: ['POST'])]
    public function updateSort(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['orders'], $data['printer'], $data['sessionId']) || !is_array($data['orders'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $shipBy = [];

        foreach ($data['orders'] as $orderData) {
            $order = $this->entityManager->getRepository(WarehouseOrder::class)->find($orderData['id']);
            if ($order) {

                $oldShipByDate = $order->getShipBy();
                $oldSortIndex = $order->getSortIndex();

                $order->setShipBy(new \DateTimeImmutable($orderData['shipBy']));
                $order->setSortIndex($orderData['sortIndex']);

                $shipBy[] = (new \DateTimeImmutable($orderData['shipBy']))->format('Y-m-d');
                if ($oldSortIndex !== $orderData['sortIndex']) {
                    $oldPosition = $oldSortIndex + 1;
                    $newPosition = $orderData['sortIndex'] + 1;

                    $message = "The card was moved within the 'Queue' board, changing its position from <b>{$oldPosition}</b> to <b>{$newPosition}</b>.";

                    $this->warehouseService->log(
                        $order,
                        $message
                    );
                }

                if ($oldShipByDate->format('Y-m-d') !== (new \DateTimeImmutable($orderData['shipBy']))->format('Y-m-d')) {
                    $shipBy[] = $oldShipByDate->format('Y-m-d');
                    $this->warehouseService->log(
                        $order,
                        "Ship by date changed from <b>{$oldShipByDate->format('D M d, Y')}</b> to <b>" . (new \DateTimeImmutable($orderData['shipBy']))->format('D M d, Y') . "</b>"
                    );
                    $this->warehouseEvents->changedShipByListEvent($order);
                }
                $this->entityManager->persist($order);
            }
        }

        $this->entityManager->flush();

        $shipBy = array_unique($shipBy);

        $this->warehouseEvents->setTriggeredBySession($data['sessionId']);
        $this->warehouseEvents->updateSortIndexEvent(shipBy: $shipBy, printer: $data['printer']);

        return new JsonResponse(['message' => 'Sort indexes updated successfully']);
    }

    #[Route('/warehouse-orders/update-note', name: 'update_warehouse_order_note', methods: ['POST'])]
    public function updateNote(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'], $data['comments'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);
        if (!$warehouseOrder) {
            return new JsonResponse(['error' => 'Warehouse Order not found'], 404);
        }


        $warehouseOrder->setComments($data['comments']);
        $this->entityManager->flush();
        $this->warehouseEvents->updateNotesEvent($warehouseOrder);

        return new JsonResponse(['message' => 'Notes updated successfully']);
    }

    #[Route('/warehouse-orders/comment', name: 'update_warehouse_order_comment', methods: ['POST'])]
    public function updateComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'], $data['comment'])) {
            return new JsonResponse(['error' => 'Invalid data: missing id or comment'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);
        if (!$warehouseOrder) {
            throw new NotFoundHttpException('Warehouse Order not found');
        }

        $wareHouseOrderLog = new WarehouseOrderLog();
        $wareHouseOrderLog->setOrder($warehouseOrder);
        $wareHouseOrderLog->setLoggedBy($this->getUser());
        $wareHouseOrderLog->setContent($data['comment']);
        $wareHouseOrderLog->setIsManual(true);
        $this->entityManager->persist($wareHouseOrderLog);
        $this->entityManager->flush();

        $this->warehouseEvents->updateCommentLogsEvent($warehouseOrder);

        return new JsonResponse(['message' => 'Comment updated successfully', 'data' => json_decode($this->serializer->serialize($warehouseOrder, 'json', [AbstractNormalizer::GROUPS => ['apiData']]))], 200);
    }

    #[Route('/warehouse-orders/remove-log', name: 'remove_warehouse_order_log', methods: ['DELETE'])]
    public function removeLog(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'], $data['logId'])) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid request: missing id, logId'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);
        if (!$warehouseOrder) {
            return new JsonResponse(['success' => false, 'error' => 'Warehouse Order not found'], 404);
        }

        $warehouseOrderLog = $this->entityManager->getRepository(WarehouseOrderLog::class)->findOneBy(['id' => $data['logId']]);
        if (!$warehouseOrderLog) {
            return new JsonResponse(['success' => false, 'error' => 'Log not found for the given Warehouse Order'], 404);
        }

        try {
            $this->entityManager->remove($warehouseOrderLog);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Failed to delete log: ' . $e->getMessage()], 500);
        }

        $this->warehouseEvents->removeCommentLogsEvent($warehouseOrder);

        return new JsonResponse([
            'success' => true,
            'message' => 'Log removed successfully',
            'data' => json_decode($this->serializer->serialize($warehouseOrder, 'json', [
                AbstractNormalizer::GROUPS => ['apiData'],
            ])),
        ], 200);
    }


    #[Route('/warehouse-orders/update-print-status', name: 'update_warehouse_order_print_status', methods: ['POST'])]
    public function updatePrintStatus(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'], $data['printStatus'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);
        if (!$warehouseOrder) {
            return new JsonResponse(['error' => 'Warehouse Order not found'], 404);
        }

        if ($warehouseOrder->getPrintStatus() === WarehouseOrderStatusEnum::DONE) {
            $this->warehouseEvents->removeWarehouseOrderEvent($warehouseOrder);
            $this->warehouseEvents->printerWithCountEvent();
            return new JsonResponse(['error' => 'Order Id: ' . $warehouseOrder->getOrder()->getOrderId() . ' - The order print status is already ' . $warehouseOrder->getPrintStatus() . ' and moved to Ready for Shipment.']);
        }

        $currentStatus = $warehouseOrder->getPrintStatus();
        $warehouseOrder->setPrintStatus($data['printStatus']);
        if (isset($data['printStatus']) && $data['printStatus'] === WarehouseOrderStatusEnum::PAUSED) {
            $warehouseOrder->getOrder()->setIsPause(true);
        } else {
            $warehouseOrder->getOrder()->setIsPause(false);
        }
        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();
        $newStatus = $warehouseOrder->getPrintStatus();

        if ($currentStatus !== $newStatus) {
            $oldStatus = isset(WarehouseOrderStatusEnum::STATUS[$currentStatus]) ? WarehouseOrderStatusEnum::STATUS[$currentStatus]['label'] : $currentStatus;
            $this->warehouseService->log($warehouseOrder, 'Status changed from <b>' . $oldStatus . '</b> to <b>' . WarehouseOrderStatusEnum::STATUS[$newStatus]['label'] . '</b>');
        }

        $this->warehouseEvents->updatePrintStatusEvent($warehouseOrder);

        return new JsonResponse(['message' => 'Print status updated successfully', 'data' => json_decode($this->serializer->serialize($warehouseOrder, 'json', [AbstractNormalizer::GROUPS => ['apiData']]))]);
    }

    #[Route('/warehouse-orders/update-proof-print', name: 'update_warehouse_order_proof_print', methods: ['POST'])]
    public function updatePrinted(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'], $data['proofPrinted'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);
        if (!$warehouseOrder) {
            return new JsonResponse(['error' => 'Warehouse Order not found'], 404);
        }

        $warehouseOrder->setIsProofPrinted($data['proofPrinted']);
        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();

        $this->warehouseService->log(
            $warehouseOrder,
            'Proof printed status changed to <b>' . ($warehouseOrder->isIsProofPrinted() ? 'Printed' : 'Not printed') . '</b>'
        );

        $this->warehouseEvents->updateProofPrintedEvent($warehouseOrder);

        return new JsonResponse(['message' => 'Proof Printed status updated successfully', 'data' => json_decode($this->serializer->serialize($warehouseOrder, 'json', [AbstractNormalizer::GROUPS => ['apiData']]))]);
    }

    #[Route('/warehouse-orders/update', name: 'update_warehouse_order', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'], $data['printerName'], $data['shipBy'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);
        if (!$warehouseOrder) {
            return new JsonResponse(['error' => 'Warehouse Order not found'], 404);
        }

        $oldWarehouseOrder = clone $warehouseOrder;

        $shipByDateChanged = false;
        $printerChanged = false;

        $shipByList = [$oldWarehouseOrder->getShipBy()->format('Y-m-d')];

        $mustShip = null;

        if (!empty($data['metaData']['mustShip']['date'])) {
            $mustShip = $data['metaData']['mustShip']['date'];
        }

        $targetShipBy = $data['shipBy'];

        if ($mustShip) {
            $targetShipBy = $mustShip;
        }
        $shipBy = new \DateTimeImmutable($targetShipBy);

        if ($data['printerName'] !== $warehouseOrder->getPrinterName()) {
            $warehouseOrder->setPrinterName($data['printerName']);
            if ($mustShip) {
                $warehouseOrder->setSortIndex(-1);
            }
            $warehouseOrder->setShipBy($shipBy);
            $printerChanged = true;

            if (!$this->warehouseService->isWarehouseOrderShipByListActive($warehouseOrder, $data['printerName'])) {
                $activateShipByList1 = $this->warehouseService->activateShipByList($warehouseOrder);
                if ($activateShipByList1 && $activateShipByList1->getShipBy() instanceof \DateTimeImmutable) {
                    $shipByList[] = $activateShipByList1->getShipBy()->format('Y-m-d');
                }
            }

            $this->warehouseEvents->createShipByListEvent($data['printerName']);
            $this->warehouseEvents->createShipByListEvent($oldWarehouseOrder->getPrinterName());
        }

        if ($shipBy !== null && $shipBy !== $warehouseOrder->getShipBy()->format('Y-m-d')) {
            $shipByDateChanged = true;
            $warehouseOrder->setShipBy($shipBy);
            if ($mustShip) {
                $warehouseOrder->setSortIndex(-1);
            }
            if (!$this->warehouseService->isWarehouseOrderShipByListActive($warehouseOrder, $data['printerName'])) {
                $activateShipByList = $this->warehouseService->activateShipByList($warehouseOrder);
                if ($activateShipByList && $activateShipByList->getShipBy() instanceof \DateTimeImmutable) {
                    $shipByList[] = $activateShipByList->getShipBy()->format('Y-m-d');
                }
            }
            $this->warehouseEvents->createShipByListEvent($data['printerName']);
        }

        $warehouseOrder->setDriveLink($data['driveLink']);
        $warehouseOrder->setShippingService($data['shippingService']);
        $warehouseOrder->getOrder()->setMetaDataKey('mustShip', $data['metaData']['mustShip'] ?? null);
        $this->warehouseService->updateOrderTags($warehouseOrder->getOrder(), $data['metaData']['tags']);

        $this->warehouseService->logChange($oldWarehouseOrder, $warehouseOrder);

        $this->entityManager->persist($warehouseOrder);
        $this->entityManager->flush();

        if ($shipByDateChanged || $printerChanged) {
            $this->warehouseEvents->changedShipByListEvent($warehouseOrder, true);
            $this->warehouseEvents->changedShipByListEvent($oldWarehouseOrder, true);
        }

        if ($data['driveLink'] !== $warehouseOrder->getDriveLink() || $data['shippingService'] !== $warehouseOrder->getShippingService()) {
            $this->warehouseEvents->updateWarehouseOrder($warehouseOrder);
        } else if ((isset($data['metaData']['tags']) && $data['metaData']['tags']) !== $warehouseOrder->getOrder()->getMetaData()['tags']) {
            $this->warehouseEvents->updateWarehouseOrder($warehouseOrder);
        }

        $shipByList[] = $warehouseOrder->getShipBy()->format('Y-m-d');

        $printerName = $warehouseOrder->getPrinterName();
        $oldPrinterName = $oldWarehouseOrder->getPrinterName();

        $this->warehouseEvents->updateShipByListEvent(shipByList: $shipByList, printer: $printerName);
        $this->warehouseEvents->updateShipByListEvent(shipByList: $shipByList, printer: $oldPrinterName);
        $this->warehouseEvents->printerWithCountEvent();

        return new JsonResponse([
            'success' => true,
            'message' => 'Warehouse Order updated successfully',
            'data' => json_decode($this->serializer->serialize($warehouseOrder, 'json', [AbstractNormalizer::GROUPS => ['apiData']])),
        ]);
    }


    #[Route('/warehouse-orders/create-ship-by', name: 'create_warehouse_order_ship_by', methods: ['POST'])]
    public function createShipBy(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['shipByDates'], $data['printer'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $printer = $data['printer'];
        $alreadyExists = 0;
        $created = 0;
        $ShipByList = [];
        foreach ($data['shipByDates'] as $shipByDate) {
            $isExists = $this->entityManager->getRepository(WarehouseShipByList::class)->findOneBy(['shipBy' => new \DateTimeImmutable($shipByDate), 'printerName' => $printer]);
            if (!$isExists) {
                $list = new WarehouseShipByList();
                $list->setShipBy(new \DateTimeImmutable($shipByDate));
                $list->setPrinterName($printer);
                $this->entityManager->persist($list);
                $this->entityManager->flush();
                $ShipByList[] = $list;
                $created++;
            } else {
                if ($isExists->getDeletedAt() !== null) {
                    $isExists->setDeletedAt(null);
                    $isExists->setUpdatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($isExists);
                    $this->entityManager->flush();
                    $created++;
                } else {
                    $alreadyExists++;
                }
            }
        }
        $message = 'We have created ' . $created . ' new ship by list(s) for printer ' . $printer;
        if ($alreadyExists > 0) {
            $message .= ' and ' . $alreadyExists . ' ship by list(s) already exists';
        }

        $this->entityManager->flush();

        if ($created > 0) {
            $this->warehouseEvents->createShipByListEvent($printer);
        }

        return new JsonResponse(['message' => $message], 200);
    }

    #[Route('/warehouse-orders/filter-orders', name: 'filter_warehouse_orders', methods: ['POST'])]
    public function searchOrders(Request $request, OrderRepository $orderRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['query']) || !is_string($data['query'])) {
            return new JsonResponse(['error' => 'Invalid query parameter'], 400);
        }

        $query = $data['query'];

        try {
            $orders = $orderRepository->getOrdersForWarehouse($query);

            if (empty($orders)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No orders found'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse(json_decode($this->serializer->serialize($orders, 'json', [AbstractNormalizer::GROUPS => ['apiData']])), 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/warehouse-orders/mark-done', name: 'mark_warehouse_order_done', methods: ['POST'])]
    public function markDone(Request $request, WarehouseOrderRepository $warehouseOrderRepository, OrderRepository $orderRepository, OrderLogger $orderLogger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['orderId'], $data['id'], $data['type'], $data['printer'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid data: orderId, id, and type are required.'
            ], Response::HTTP_OK);
        }

        $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->find($data['id']);

        $printer = $warehouseOrder->getPrinterName();

        if (!$warehouseOrder) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Warehouse order not found.'
            ], Response::HTTP_OK);
        }

        $userOrder = $warehouseOrder->getOrder();

        switch (strtoupper($data['type'])) {
            case WarehouseMercureEventEnum::MARK_DONE:
                $userOrder->setStatus(OrderStatusEnum::COMPLETED);
                $userOrder->setUpdatedAt(new \DateTimeImmutable());
                $orderRepository->save($userOrder, true);


                if ($userOrder->getParent() && $userOrder->isSplitOrder() && $this->warehouseService->checkIfSubOrdersAreReady($warehouseOrder)) {
                    $userOrder->getParent()->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
                    $orderRepository->save($userOrder->getParent(), true);
                }

                $orderLogger->setOrder($userOrder);
                $orderLogger->log('The order has been marked as <b>Done</b> in the Order Queue and moved into the Completed orders.');

                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $warehouseOrderRepository->save($warehouseOrder, true);

                $this->warehouseEvents->markDoneEvent($warehouseOrder, $data['type']);
                $this->warehouseEvents->removeWarehouseOrderEvent($warehouseOrder);
                $this->warehouseEvents->printerWithCountEvent();
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Order #' . $userOrder->getOrderId() . ' has been marked as done successfully.'
                ], Response::HTTP_OK);

            case WarehouseMercureEventEnum::FREIGHT_SHIPPING_DONE:
                $userOrder->setStatus(OrderStatusEnum::COMPLETED);
                $userOrder->setShippingMethod(ShippingEnum::FREIGHT);
                $userOrder->setShippingDate(new \DateTimeImmutable());
                $userOrder->setShippingOrderId($userOrder->getOrderId());
                $userOrder->setShippingCarrier(ShippingEnum::FREIGHT);
                $userOrder->setShippingStatus('freight_done');

                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $warehouseOrderLog = new WarehouseOrderLog();
                $warehouseOrderLog->setOrder($warehouseOrder);
                $warehouseOrderLog->setContent('The order has been marked as <b>Freight Shipping Done</b>');
                $warehouseOrderLog->setLoggedBy($this->getUser());
                $warehouseOrderLog->setCreatedAt(new \DateTimeImmutable());
                $warehouseOrder->addWarehouseOrderLog($warehouseOrderLog);

                $warehouseOrderRepository->save($warehouseOrder, true);
                $orderRepository->save($userOrder, true);

                $orderLogger->setOrder($userOrder);
                $orderLogger->log('The order has been marked as "Freight Shipping Done"');

                $this->warehouseEvents->markFreightShippingDoneEvent($warehouseOrder, $data['type']);
                $this->warehouseEvents->removeWarehouseOrderEvent($warehouseOrder);
                $this->warehouseEvents->printerWithCountEvent();
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Order #' . $userOrder->getOrderId() . ' has been marked as "Freight Shipping Done" successfully.'
                ], Response::HTTP_OK);

            case WarehouseMercureEventEnum::PICKUP_DONE:
                $userOrder->setStatus(OrderStatusEnum::COMPLETED);
                $userOrder->setShippingMethod(ShippingEnum::PICKUP);
                $userOrder->setShippingDate(new \DateTimeImmutable());
                $userOrder->setShippingOrderId($userOrder->getOrderId());
                $userOrder->setShippingCarrier(ShippingEnum::PICKUP);
                $userOrder->setShippingStatus('pickup_done');

                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $warehouseOrderLog = new WarehouseOrderLog();
                $warehouseOrderLog->setOrder($warehouseOrder);
                $warehouseOrderLog->setContent('The order has been marked as "Pickup Done"');
                $warehouseOrderLog->setLoggedBy($this->getUser());
                $warehouseOrderLog->setCreatedAt(new \DateTimeImmutable());
                $warehouseOrder->addWarehouseOrderLog($warehouseOrderLog);

                $warehouseOrderRepository->save($warehouseOrder, true);
                $orderRepository->save($userOrder, true);

                $orderLogger->setOrder($userOrder);
                $orderLogger->log('The order has been marked as <b>Pickup Done</b>');

                $this->warehouseEvents->markPickupDoneEvent($warehouseOrder, $data['type']);
                $this->warehouseEvents->removeWarehouseOrderEvent($warehouseOrder);
                $this->warehouseEvents->printerWithCountEvent();
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Order #' . $userOrder->getOrderId() . ' has been marked as "Pickup Done" successfully.'
                ], Response::HTTP_OK);

            case WarehouseMercureEventEnum::MARK_DONE_READY_FOR_SHIPMENT:
                // if ($userOrder->getShippingOrderId()) {
                //     return new JsonResponse([
                //         'success' => false,
                //         'message' => 'Order already registered in shipping system.'
                //     ], Response::HTTP_OK);
                // }

                if (!in_array($userOrder->getStatus(), [OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::SHIPPED])) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => "Order status must be 'Ready for Shipment' or 'Sent for Production' to be marked as 'Ready for Shipment'."
                    ], Response::HTTP_OK);
                }

                // mark as ready to ship
                $userOrder = $warehouseOrder->getOrder();
                if ($userOrder->getParent() && $userOrder->isSplitOrder()) {
                    $userOrder->setStatus(OrderStatusEnum::COMPLETED);
                    $userOrder->setShippingStatus(ShippingStatusEnum::SHIPPED);
                } else {
                    $userOrder->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
                    $shippingStatus = $userOrder->getShippingStatus();
                    if ($shippingStatus === ShippingStatusEnum::LABEL_PURCHASED || $shippingStatus === ShippingStatusEnum::SHIPPED) {
                        $userOrder->setShippingStatus(ShippingStatusEnum::LABEL_PURCHASED);
                    } else {
                        $userOrder->setShippingStatus(ShippingStatusEnum::READY_FOR_SHIPMENT);
                    }
                }

                if ($userOrder->getParent() && $userOrder->isSplitOrder() && $this->warehouseService->checkIfSubOrdersAreReady($warehouseOrder)) {
                    $userOrder->getParent()->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
                    $orderRepository->save($userOrder->getParent(), true);
                }

                $userOrder->setShippingMethod(ShippingEnum::EASYPOST);
                $userOrder->setUpdatedAt(new \DateTimeImmutable());
                $orderRepository->save($userOrder, true);

                $orderLogger->setOrder($userOrder);
                $orderLogger->log('The order has been marked as <b>Done</b> in the Order Queue and moved into the <b>Create Shipment</b> order tab for a further shipment process.');

                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $warehouseOrder->setUpdatedAt(new \DateTimeImmutable());
                $warehouseOrderRepository->save($warehouseOrder, true);

                $this->warehouseEvents->markDoneReadyForShipmentEvent($warehouseOrder, $data['type']);
                $this->warehouseEvents->removeWarehouseOrderEvent($warehouseOrder);
                $this->warehouseEvents->printerWithCountEvent();
                $response = [
                    'success' => true,
                    'message' => 'Order #' . $userOrder->getOrderId() . ' has been marked as done successfully and moved into the <b>Create Shipment</b> order tab for a further shipment process.'
                ];

                if (!$response['success']) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $response['message']
                    ], Response::HTTP_OK);
                }

                $orderLogger->setOrder($userOrder);
                $orderLogger->log('Order has been moved into the Create Shipment order tab with Order Id: ' . $userOrder->getShippingOrderId());
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Order #' . $userOrder->getOrderId() . ' successfully  moved into the Create Shipment order tab for a further shipment process.'
                ], Response::HTTP_OK);

            default:
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid type provided. Valid keys are: MARK_DONE, FREIGHT_SHIPPING_DONE, PICKUP_DONE, PUSH_TO_SE, MARK_DONE_READY_FOR_SHIPMENT.'
                ], Response::HTTP_OK);
        }
    }

    #[Route('/warehouse-orders/add-orders', name: 'warehouse_orders_add', methods: ['POST'])]
    public function addOrders(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        if (!isset($data['printerName'], $data['orders'], $data['shipBy']) && empty($data['orders'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $printerName = $data['printerName'];
        $orders = $data['orders'];
        $shipBy = new \DateTimeImmutable($data['shipBy']);
        $shipByList = [$shipBy->format('Y-m-d')];
        try {

            foreach ($orders as $orderId) {
                $order = $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);
                if ($order) {
                    $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['order' => $order]);
                    if ($warehouseOrder && $warehouseOrder->getShipBy()) {
                        $shipByList[] = $warehouseOrder->getShipBy()->format('Y-m-d');
                    }
                    $this->warehouseService->AddOrUpdateWarehouseOrder($order, $printerName, $shipBy);
                }
            }

            $this->warehouseEvents->updateShipByListEvent(shipByList: $shipByList, printer: $printerName);
            $this->warehouseEvents->printerWithCountEvent();
            return new JsonResponse(['message' => 'Orders updated successfully'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/warehouse-orders/delete-ship-by-list/{id}', name: 'delete_ship_by_list', methods: ['DELETE'])]
    public function deleteShipByList(
        WarehouseShipByList $list,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {

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
            return $this->json([
                'status' => 'error',
                'message' => 'Ship by list cannot be deleted because it has orders. Please move orders first to another ship by list to delete this list.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $list->setDeletedAt(new \DateTimeImmutable());
        $entityManager->persist($list);
        $entityManager->flush();

        if ($ordersInList <= 0) {
            $this->warehouseEvents->removeShipByListEvent($list);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Ship by list has been deleted successfully.'
        ], Response::HTTP_OK);
    }

    #[Route('/warehouse-orders/group-orders', name: 'warehouse_orders_group_orders', methods: ['POST'])]
    public function groupOrders(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['printer'], $data['shipBy'], $data['orders'], $data['selectedWarehouseOrder']) && !is_array($data['orders']) && empty($data['orders'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid data'
            ], 400);
        }

        $printer = $data['printer'];
        $shipBy = new \DateTimeImmutable($data['shipBy']);
        $warehouseOrders = $data['orders'];
        $selectedWarehouseOrder = $data['selectedWarehouseOrder'];

        try {

            $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['id' => $selectedWarehouseOrder]);
            if (!$warehouseOrder) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Selected warehouse order not found'
                ], Response::HTTP_BAD_REQUEST);
            }


            return new JsonResponse([
                'success' => 'true',
                'message' => 'Orders grouped successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/order/proof', name: 'order_proof', methods: ['POST'])]
    public function generateProofLink(Request $request, VichS3Helper $s3Helper): JsonResponse
    {
        try {

            $data = json_decode($request->getContent(), true);

            if (!isset($data['orderId'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid data'
                ], Response::HTTP_OK);
            }

            $orderId = $data['orderId'];
            $order = $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);
            if (!$order) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Order not found'
                ], Response::HTTP_OK);
            }

            $approvedProof = $order->getApprovedProof();

            $link = null;
            $image = null;
            foreach ($approvedProof->getFiles() as $file) {
                if ($file->getType() == 'PROOF_IMAGE') {
                    $image = $s3Helper->asset($file, 'fileObject');
                } elseif ($file->getType() == 'PROOF_FILE') {
                    $link = $s3Helper->asset($file, 'fileObject');
                }
            }

            $warehouseOrder = $this->entityManager->getRepository(WarehouseOrder::class)->findOneBy(['order' => $order]);
            $warehouseOrder->setProofPrintedBy($this->getUser());
            $warehouseOrder->setProofPrintedAt(new \DateTimeImmutable());

            $this->entityManager->persist($warehouseOrder);
            $this->entityManager->flush();

            $printedAt = $warehouseOrder->getProofPrintedAt()?->format('Y-m-d H:i:s') ?? 'N/A';
            $printedByName = $warehouseOrder->getProofPrintedBy()?->getName() ?? 'N/A';
            $printedByEmail = $warehouseOrder->getProofPrintedBy()?->getEmail() ?? 'N/A';

            $linkHtml = $link ? "<a href=\"{$link}\" target=\"_blank\">Proof File</a>" : 'N/A';
            $imageHtml = $image ? "<a href=\"{$image}\" target=\"_blank\">Proof Image</a>" : 'N/A';

            $message = <<<LOG
                Proof Document Generated<br>
                -------------------------<br>
                Proof Printed At: {$printedAt}<br>
                Printed By: {$printedByName} ({$printedByEmail})<br><br>
                Proof Link: {$linkHtml}<br>
                Proof Image: {$imageHtml}<br><br>
                Action: Proof marked as printed and link generated.
            LOG;

            $this->warehouseService->addWarehouseOrderLog($warehouseOrder, $message);

            $this->warehouseEvents->updateWarehouseOrder($warehouseOrder);


            return new JsonResponse([
                'success' => true,
                'data' => $link ?? $image
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/sessions/heartbeat', name: 'session_heartbeat', methods: ['GET'])]
    public function heartbeat(): JsonResponse
    {
        $this->warehouseEvents->sessionHeartbeatEvent();
        $this->warehouseEvents->printerWithCountEvent();
        return new JsonResponse([
            'message' => 'Session heartbeat',
            'status' => 200,
            'success' => true
        ]);
    }

    #[Route('/generate/jwt-token', name: 'generate_jwt_token', methods: ['GET'])]
    public function generateJwtToken(TokenProvider $tokenProvider): JsonResponse
    {
        $token = $tokenProvider->getJwt();

        if (!$token) {
            return new JsonResponse(['error' => 'Failed to generate JWT token'], 500);
        }

        // Set token expiration (2 days from now)
        $expireDate = (new \DateTimeImmutable('+2 days'));
        $expiresIn = $expireDate->getTimestamp() * 1000; // Convert to milliseconds for JavaScript

        setcookie('MERCURE_JWT_EXPIRE', (string) $expiresIn, $expireDate->getTimestamp(), '/');

        return new JsonResponse([
            'token' => $token,
            'expiresIn' => $expiresIn
        ]);
    }
}
