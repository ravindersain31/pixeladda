<?php

namespace App\Controller\Admin\Order;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderLog;
use App\Entity\Order;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\ShippingEnum;
use App\Event\OrderShippedEvent;
use App\Repository\Admin\WarehouseOrderRepository;
use App\Repository\OrderRepository;
use App\Service\Admin\ShippingEasy\ShippingEasy;
use App\Service\CogsHandlerService;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShippingEasyController extends AbstractController
{
    #[Route('/orders/{orderId}/push-to-shipping-easy', name: 'order_push_to_shipping_easy')]
    public function pushToSE(string $orderId, Request $request, OrderRepository $repository, EntityManagerInterface $entityManager, ShippingEasy $shippingEasy, OrderLogger $orderLogger): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }
        if ($order->getShippingOrderId()) {
            $this->addFlash('warning', 'This order #'.$order->getOrderId().' is already registered in the Shippingeasy. Please login <a target=\"_blank\" href=https://app4.shippingeasy.com/orders>https://app4.shippingeasy.com/orders</a>');
        } else if (in_array($order->getStatus(), [OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SENT_FOR_PRODUCTION])) {
            $response = $shippingEasy->createOrder($order);
            if ($response['success']) {
                $this->addFlash('success', 'The order #'.$order->getOrderId().' has been successfully submitted to Shippingeasy portal. Login <a target=\"_blank\" href=https://app4.shippingeasy.com/orders>https://app4.shippingeasy.com/orders</a>');
                $orderLogger->setOrder($order);
                $orderLogger->log('Order has been pushed to Shipping Easy with SE Order Id: ' . $order->getShippingOrderId());
            } else {
                $this->addFlash('danger', $response['message']);
            }
        } else {
            $this->addFlash('danger', 'Only orders with status "Ready for Shipment" or "Entered into Shippingeasy" can be pushed to Shippingeasy');
        }
        $from = $request->query->get('from');
        if ($from === 'queue') {
            $warehouseOrder = $order->getWarehouseOrder();
            if ($warehouseOrder) {
                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $entityManager->persist($warehouseOrder);
                $entityManager->flush();

                $order->setStatus(OrderStatusEnum::READY_FOR_SHIPMENT);
                $repository->save($order, true);

                $orderLogger->setOrder($order);
                $orderLogger->log('The order has been marked as <b>Done</b> in the Order Queue');

                return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $warehouseOrder->getPrinterName()]);
            }
            return $this->redirectToRoute('admin_warehouse_queue_done');
        }
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
    }

    #[Route('/orders/{orderId}/mark-pickup-done/{printer}', name: 'order_pickup_done', defaults: ['printer' => 'OVERVIEW'])]
    public function pickupDone(string $orderId, string $printer, Request $request, OrderRepository $repository, WarehouseOrderRepository $warehouseOrderRepository, OrderLogger $orderLogger): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        if (in_array($order->getStatus(),[OrderStatusEnum::SHIPPED, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SENT_FOR_PRODUCTION])) {
            $order->setStatus(OrderStatusEnum::COMPLETED);
            $order->setShippingMethod(ShippingEnum::PICKUP);
            $order->setShippingDate(new \DateTimeImmutable());
            $order->setShippingOrderId($order->getOrderId());
            $order->setShippingCarrier(ShippingEnum::PICKUP);
            $order->setShippingStatus('pickup_done');

            $warehouseOrder = $order->getWarehouseOrder();
            if($warehouseOrder instanceof WarehouseOrder) {
                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $warehouseOrderLog = new WarehouseOrderLog();
                $warehouseOrderLog->setOrder($warehouseOrder);
                $warehouseOrderLog->setContent('The order has been marked as "Pickup Done"');
                $warehouseOrderLog->setLoggedBy($this->getUser());
                $warehouseOrderLog->setCreatedAt(new \DateTimeImmutable());
                $warehouseOrder->addWarehouseOrderLog($warehouseOrderLog);
                $warehouseOrderRepository->save($warehouseOrder, true);
            }
            $repository->save($order, true);
            $orderLogger->setOrder($order);
            $orderLogger->log('The order has been marked as <b>Pickup Done</b>');
            $this->addFlash('success', 'The order #' . $order->getOrderId() . ' has been successfully marked as "Pickup Done"');
        } else {
            $this->addFlash('danger', "Orders with the status 'Ready for ShippingEasy,' 'Entered in ShippingEasy,' or 'Shipped' can only be marked as 'Pickup Done.'");
        }
        $from = $request->query->get('from');
        if ($from === 'queue' && $printer !== 'OVERVIEW') {
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $printer]);
        }
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
    }


    #[Route('/orders/{orderId}/mark-freight-done/{printer}', name: 'order_freight_done', defaults: ['printer' => 'OVERVIEW'])]
    public function freightDone(string $orderId, string $printer, Request $request, OrderRepository $repository, WarehouseOrderRepository $warehouseOrderRepository, OrderLogger $orderLogger): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        if (in_array($order->getStatus(),[OrderStatusEnum::SHIPPED, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SENT_FOR_PRODUCTION])) {
            $order->setStatus(OrderStatusEnum::COMPLETED);
            $order->setShippingMethod(ShippingEnum::FREIGHT);
            $order->setShippingDate(new \DateTimeImmutable());
            $order->setShippingOrderId($order->getOrderId());
            $order->setShippingCarrier(ShippingEnum::FREIGHT);
            $order->setShippingStatus('freight_done');

            $warehouseOrder = $order->getWarehouseOrder();
            if($warehouseOrder instanceof WarehouseOrder) {
                $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::DONE);
                $warehouseOrderLog = new WarehouseOrderLog();
                $warehouseOrderLog->setOrder($warehouseOrder);
                $warehouseOrderLog->setContent('The order has been marked as <b>Freight Shipping Done</b>');
                $warehouseOrderLog->setLoggedBy($this->getUser());
                $warehouseOrderLog->setCreatedAt(new \DateTimeImmutable());
                $warehouseOrder->addWarehouseOrderLog($warehouseOrderLog);
                $warehouseOrderRepository->save($warehouseOrder, true);
            }
            $repository->save($order, true);
            $orderLogger->setOrder($order);
            $orderLogger->log('The order has been marked as "Freight Shipping Done"');
            $this->addFlash('success', 'The order #' . $order->getOrderId() . ' has been successfully marked as "Freight Shipping Done"');
        } else {
            $this->addFlash('danger', "Orders with the status 'Ready for ShippingEasy,' 'Entered in ShippingEasy,' or 'Shipped' can only be marked as 'Freight Shipping Done.'");
        }
        $from = $request->query->get('from');
        if ($from === 'queue' && $printer !== 'OVERVIEW') {
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $printer]);
        }
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
    }

    #[Route('/orders/{orderId}/update-se-status', name: 'order_update_shipping_easy_status')]
    public function updateShippingEasyStatus(string $orderId, OrderRepository $repository, ShippingEasy $shippingEasy, OrderLogger $orderLogger): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $shipments = [];
        $seOrderStatus = null;
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

                    $orderLogger->setOrder($order);
                    $orderLogger->log('This order has been shipped via ' . $order->getShippingCarrier() . ' (' . $order->getShippingCarrierService() . ') and tracking number is ' . $order->getShippingTrackingId());
                    $last15Days = new \DateTimeImmutable('-15 days');
                    if ($order->getShippingDate()->getTimestamp() < $last15Days->getTimestamp()) {
                        $order->setStatus(OrderStatusEnum::COMPLETED);
                        $orderLogger->log('The order has been marked as completed as the shipping date is older than 15 days');
                    } else {
                        $order->setStatus(OrderStatusEnum::SHIPPED);
                    }
                }
            }
            $repository->save($order, true);
        }

        dd($shipments, $seOrderStatus);
    }

    #[Route('/se-orders', name: 'order_shipping_easy_orders')]
    public function seOrders(ShippingEasy $shippingEasy): Response
    {

        $orders = $shippingEasy->getOrders(status: 'ready_for_shipment');
        dd($orders);
    }

    #[Route('/orders/{orderId}/sync-tracking', name: 'order_shipping_easy_sync_tracking')]
    public function syncTrackingSE(string $orderId, OrderRepository $repository, ShippingEasy $shippingEasy, OrderLogger $orderLogger, EventDispatcherInterface $eventDispatcher, CogsHandlerService $cogs): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $response = $shippingEasy->getOrder($order->getShippingOrderId());
        if ($response['success']) {
            $seOrder = $response['data']['order'];
            $shipments = $seOrder['shipments'];
            $shipment = reset($shipments);
            if ($shipment && in_array($shipment['workflow_state'], ['label_ready', 'label_printed']) && $order->getStatus() !== OrderStatusEnum::SHIPPED) {
                $shipmentCost = $shipment['shipment_cost'] / 100;
                $order->setShippingStatus($seOrder['order_status']);
                $order->setShippingCarrier($shipment['carrier_key']);
                $order->setShippingCarrierService($shipment['carrier_service_key']);
                $order->setShippingTrackingId($shipment['tracking_number']);
                $order->setShippingCost($shipment['shipment_cost'] / 100);
                $order->setShippingDate(new \DateTimeImmutable($shipment['ship_date']));
                $order->setShippingMetaDataKey('shipments', $shipments);
                $order->setCompanyShippingCost($shipmentCost);

                $order->setStatus(OrderStatusEnum::SHIPPED);

                $repository->save($order, true);

                $orderLogger->setOrder($order);
                $orderLogger->log('This order has been shipped via ' . $order->getShippingCarrier() . ' (' . $order->getShippingCarrierService() . ') and tracking number is ' . $order->getShippingTrackingId());

                $eventDispatcher->dispatch(new OrderShippedEvent($order), OrderShippedEvent::NAME);

                $cogs->syncShippingCost($order->getStore(), $order->getOrderAt());
                $this->addFlash('success', 'The tracking information has been successfully synced with the order. The order status has been updated to "Shipped"');
            } else {
                $this->addFlash('warning', 'The label has not been generated yet. Please wait for the label to be generated before syncing the tracking information');
            }
        }

        return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
    }

}
