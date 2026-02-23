<?php

namespace App\Controller\Cron;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Event\OrderShippedEvent;
use App\Repository\OrderRepository;
use App\Service\Admin\ShippingEasy\ShippingEasy;
use App\Service\CogsHandlerService;
use App\Service\OrderLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncShippingEasyController extends AbstractController
{

    public function __construct(
        private readonly OrderRepository            $orderRepository,
        private readonly OrderLogger                $orderLogger,
        private readonly ShippingEasy               $shippingEasy,
        private readonly CogsHandlerService         $cogs,
        private readonly EventDispatcherInterface   $eventDispatcher,
    )
    {
    }

    #[Route(path: '/update-entered-se', name: 'cron_update_entered-se')]
    public function index(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }
        $updatedOrderShippingStatus = $this->checkAndUpdateShippingStatus();
        $numberOfOrdersMarkedComplete = $this->markCompletedOrders10DaysOlder();

        return $this->json([
            'status' => 'ok',
            'date' => $date->format('Y-m-d H:i:s'),
            'completed' => $numberOfOrdersMarkedComplete,
            'updated' => $updatedOrderShippingStatus
        ]);
    }

    private function markCompletedOrders10DaysOlder(): int
    {
        $orders = $this->orderRepository->ordersToBeMarkedAsCompleted();
        foreach ($orders as $order) {
            $order->setStatus(OrderStatusEnum::COMPLETED);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $this->orderLogger->setOrder($order);
            $this->orderLogger->log('The order has been marked as completed as the shipping date is older than 30 days');
            $this->orderRepository->save($order, true);
        }
        return count($orders);
    }

    private function checkAndUpdateShippingStatus(): array
    {
        $updated = [];
        $orders = $this->orderRepository->ordersMarkedAsReadyForShippment();

        foreach ($orders as $order) {
            $response = $this->shippingEasy->getOrder($order['shippingOrderId']);
            if ($response['success']) {
                $order = $this->orderRepository->findByOrderId($order['orderId']);
                $seOrder = $response['data']['order'];
                $seOrderStatus = $seOrder['order_status'];
                if ($seOrderStatus === 'shipped') {
                    $shipments = $seOrder['shipments'];
                    $shipment = reset($shipments);
                    $order->setShippingStatus($seOrderStatus);
                    if ($shipment && in_array($shipment['workflow_state'], ['label_ready', 'label_printed'])) {
                        $order->setShippingMetaDataKey('shipments', $shipments);
                        $this->updateOrderWithShippingDetails($order, $shipment);
                        $updated[] = $order->getOrderId();
                    }

                    if (in_array($shipment['workflow_state'], ['cancelled'])) {
                        $this->orderLogger->setOrder($order);
                        $this->orderLogger->log('This shipment has been cancelled in Shipping Easy.');
                    }
                }
            }
        }

        return $updated;
    }

    private function updateOrderWithShippingDetails(Order $order, array $shipment): void
    {
        $shipmentCost = $shipment['shipment_cost'] / 100;
        $order->setShippingCarrier($shipment['carrier_key']);
        $order->setShippingCarrierService($shipment['carrier_service_key']);
        $order->setShippingTrackingId($shipment['tracking_number']);
        $order->setShippingCost($shipmentCost);
        $order->setShippingDate(new \DateTimeImmutable($shipment['ship_date']));
        $order->setCompanyShippingCost($shipmentCost);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $order->setStatus(OrderStatusEnum::SHIPPED);

        $this->orderRepository->save($order, true);

        $this->orderLogger->setOrder($order);
        $this->orderLogger->log('This order has been shipped via ' . $order->getShippingCarrier() . ' (' . $order->getShippingCarrierService() . ') and tracking number is ' . $order->getShippingTrackingId());
        $this->eventDispatcher->dispatch(new OrderShippedEvent($order), OrderShippedEvent::NAME);
        $this->cogs->syncShippingCost($order->getStore(), $order->getOrderAt());
    }

    private function updatedSe(Request $request, OrderRepository $repository, ShippingEasy $shippingEasy, OrderLogger $orderLogger): JsonResponse
    {
        $limit = $request->query->get('limit', 10);
        $orders = $repository->getReadyForShipment()->setMaxResults($limit)->getResult();
        $shipped = [];
        $completed = [];
        $rest = [];
        foreach ($orders as $order) {
            $response = $shippingEasy->getOrder($order->getShippingOrderId());
            if ($response['success']) {
                $seOrder = $response['data']['order'];
                $seOrderStatus = $seOrder['order_status'];
                $order->setShippingStatus($seOrderStatus);
                if ($seOrderStatus === 'shipped') {
                    $shipments = $seOrder['shipments'];
                    $shipment = reset($shipments);
                    if ($shipment && in_array($shipment['workflow_state'], ['label_ready', 'label_printed']) && $order->getStatus() !== OrderStatusEnum::SHIPPED) {
                        $shipmentCost = $shipment['shipment_cost'] / 100;
                        $order->setShippingCarrier($shipment['carrier_key']);
                        $order->setShippingCarrierService($shipment['carrier_service_key']);
                        $order->setShippingTrackingId($shipment['tracking_number']);
                        $order->setShippingCost($shipment['shipment_cost'] / 100);
                        $order->setShippingDate(new \DateTimeImmutable($shipment['ship_date']));
                        $order->setShippingMetaDataKey('shipments', $shipments);
                        $order->setCompanyShippingCost($shipmentCost);
                        $order->setUpdatedAt(new \DateTimeImmutable());

                        $orderLogger->setOrder($order);
                        $orderLogger->log('This order has been shipped via ' . $order->getShippingCarrier() . ' (' . $order->getShippingCarrierService() . ') and tracking number is ' . $order->getShippingTrackingId());
                        $last15Days = new \DateTimeImmutable('-15 days');
                        if ($order->getShippingDate()->getTimestamp() < $last15Days->getTimestamp()) {
                            $order->setStatus(OrderStatusEnum::COMPLETED);
                            $orderLogger->log('The order has been marked as completed as the shipping date is older than 15 days');
                            $completed[$order->getOrderId()] = $order->getShippingTrackingId();
                        } else {
                            $order->setStatus(OrderStatusEnum::SHIPPED);
                            $shipped[$order->getOrderId()] = $order->getShippingTrackingId();
                        }
                    }
                } else {
                    $rest[$order->getOrderId()] = $seOrderStatus;
                }
                $repository->save($order, true);
            }
        }
        return $this->json([
            'status' => 'ok',
            'shipped' => $shipped,
            'completed' => $completed,
            'rest' => $rest
        ]);
    }


}