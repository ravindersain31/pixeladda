<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderShipment;
use App\Enum\OrderStatusEnum;
use App\Event\OrderShippedEvent;
use App\Service\Admin\ShippingEasy\Signature;
use App\Service\CogsHandlerService;
use App\Service\OrderLogger;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShippingEasyController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/shipping-easy/callback', name: 'shipping_easy_callback')]
    public function shippingEasyCallback(Request $request, EntityManagerInterface $entityManager, Signature $signature, ParameterBagInterface $parameterBag, OrderLogger $orderLogger, CogsHandlerService $cogs): Response
    {
        $shipment = $request->get('shipment', []);
        $apiSignature = $request->get('api_signature', []);

        $body = json_decode($request->getContent(), true);

        $params = $request->query->all();
        unset($params['api_signature']);

        $signature->setApiSecret($parameterBag->get('SHIPPING_EASY_API_SECRET'));
        $signature->setHttpMethod($request->getMethod());
        $signature->setPath($request->getPathInfo());
        $signature->setParams($params);
        $signature->setBody($body);

        if (isset($shipment['orders'])) {
            // Divide by 100 to get $ amount.
            $shipmentCost = $shipment['shipment_cost'] / 100;
            foreach ($shipment['orders'] as $shippedOrder) {
                $order = $entityManager->getRepository(Order::class)->findOneBy(['shippingOrderId' => $shippedOrder['id']]);
                if ($order instanceof Order) {
                    if (in_array($shipment['workflow_state'], ['label_ready', 'label_printed']) && $order->getStatus() !== OrderStatusEnum::SHIPPED) {
                        if (isset($shippedOrder['order_status'])) {
                            $order->setShippingStatus($shippedOrder['order_status']);
                        }

                        $orderShipment = $order->getOrderShipments()->first();
                        if ($orderShipment instanceof OrderShipment) {
                            $orderShipment->setCarrier($shipment['carrier_key']);
                            $orderShipment->setService($shipment['carrier_service_key']);
                            $orderShipment->setTrackingId($shipment['tracking_number']);
                            $orderShipment->setSelectedRate([
                                'id' => null,
                                'rate' => $shipment['shipment_cost'] / 100,
                                'carrier' => $shipment['carrier_key'],
                                'service' => $shipment['carrier_service_key'],
                                'currency' => 'USD',
                                'shipment_id' => null,
                                'delivery_days' => null,
                                'est_delivery_days' => null,
                            ]);
                            $orderShipment->setStatus($shippedOrder['order_status']);
                            $orderShipment->setMetaDataKey('shipments', $shippedOrder['shipments']);
                            $orderShipment->setMetaDataKey('body', $body);
                        } else {
                            $order->setShippingCarrier($shipment['carrier_key']);
                            $order->setShippingCarrierService($shipment['carrier_service_key']);
                            $order->setShippingTrackingId($shipment['tracking_number']);
                            $order->setShippingCost($shipment['shipment_cost'] / 100);
                            $order->setShippingDate(new \DateTimeImmutable($shipment['ship_date']));
                            $order->setShippingMetaDataKey('shipments', $shippedOrder['shipments']);
                            $order->setShippingMetaDataKey('body', $body);
                            $order->setShippingMetaDataKey('gen_signature', $signature->encrypted());
                            $order->setShippingMetaDataKey('api_signature', $apiSignature);
                            $order->setCompanyShippingCost($shipmentCost);
                        }

                        $order->setStatus(OrderStatusEnum::SHIPPED);

                        $entityManager->persist($order);
                        $entityManager->flush();

                        $orderLogger->setOrder($order);
                        $orderLogger->log('This order has been shipped via ' . $order->getShippingCarrier() . ' (' . $order->getShippingCarrierService() . ') and tracking number is ' . $order->getShippingTrackingId());

                        $this->eventDispatcher->dispatch(new OrderShippedEvent($order), OrderShippedEvent::NAME);

                        $cogs->syncShippingCost($order->getStore(), $order->getOrderAt());
                    }

                    if (in_array($shipment['workflow_state'], ['cancelled'])) {
                        $orderLogger->setOrder($order);
                        $orderLogger->log('This shipment has been cancelled in Shipping Easy.');
                    }

                    $message = 'Order Id: ' . $order->getOrderId() . ' has been shipped via ' . $order->getShippingCarrier() . ' (' . $order->getShippingCarrierService() . ') and tracking number is ' . $order->getShippingTrackingId();
                }
            }
        }

        return $this->json([
            'status' => 'ok',
            'data' => $shipment,
        ]);
    }


}