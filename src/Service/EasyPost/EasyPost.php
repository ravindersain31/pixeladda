<?php

namespace App\Service\EasyPost;

use App\Entity\Order;
use App\Enum\OrderShipmentTypeEnum;
use App\Service\EasyPost\EasyPostCustomsItem;
use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPost extends Base
{
    use EasyPostAwareTrait;

    const SHIPPING_METHOD = 'EASYPOST';

    private OrderShipmentTypeEnum $type = OrderShipmentTypeEnum::DELIVERY;

    private ?string $toAddressId = null;

    private EasyPostShipment $easyPostShipment;

    private EasyPostOrder $easyPostOrder;

    private EasyPostCustoms $easyPostCustoms;
    private \App\Service\EasyPost\EasyPostCustomsItem $easyPostCustomsItem;

    public function __construct(ParameterBagInterface $parameterBag, EasyPostCustomsItem $easyPostCustomsItem)
    {
        parent::__construct($parameterBag);
        $this->address = new EasyPostAddress($parameterBag);
        $this->parcel = new EasyPostParcel($parameterBag);
        $this->easyPostShipment = new EasyPostShipment($parameterBag);
        $this->easyPostOrder = new EasyPostOrder($parameterBag);
        $this->easyPostCustoms = new EasyPostCustoms($parameterBag);
        $this->easyPostCustomsItem = $easyPostCustomsItem;
    }

    public function buy(string $shipmentOrOrderID, array $rate): array
    {
        $typeOfShipment = str_contains($shipmentOrOrderID, 'shp_') ? 'shipment' : 'order';
        try {
            if ($typeOfShipment === 'shipment') {
                $response = $this->easyPostShipment->buy($shipmentOrOrderID, $rate);
            } else {
                $response = $this->easyPostOrder->buy($shipmentOrOrderID, $rate['carrier'], $rate['service']);
            }
            return $response;
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function refund(string $shipmentId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->shipment->refund($shipmentId);
            $data = $response->__toArray();
            return [
                'success' => true,
                'refund' => [
                    'id' => $data['id'],
                    'status' => $data['status'],
                    'refund_status' => $data['refund_status'],
                ],
            ];
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function create(array $parcels, Order $order): array
    {
        $isSaturdayDelivery = $order->getMetaDataKey('isSaturdayDelivery') ?? false;

        $epParcels = $this->parcel->createParcels($parcels);
        if (count($parcels) > 1) {
            $this->easyPostOrder->setToAddressId($this->toAddressId);
            $this->easyPostOrder->setFromAddressId($this->getFromAddressId());
            $this->easyPostOrder->setReturnAddressId($this->getReturnAddressId());

            $shipmentsPayload = [];
            foreach ($epParcels as $epParcel) {
                $this->easyPostShipment->setToAddressId($this->toAddressId);
                $this->easyPostShipment->setFromAddressId($this->getFromAddressId());
                $this->easyPostShipment->setReturnAddressId($this->getReturnAddressId());
                $this->easyPostShipment->setParcelId($epParcel['created']['id']);
                $this->easyPostShipment->setType($this->type);

                $this->easyPostShipment->setOptions([
                    'saturday_delivery' => $isSaturdayDelivery,
                    'print_custom_1' => $order->getOrderId(),
                ]);

                if ($order->isInternational()) {
                    $customsInfo = $this->createCustomsInfo([$epParcel], $order->getCustomsForm());
                    $this->easyPostShipment->setCustomsInfo($customsInfo);
                }

                $payload = $this->easyPostShipment->makePayload();
                if (!$payload['success']) {
                    return $payload;
                }
                $shipmentsPayload[] = $payload['shipment'];
            }
            $this->easyPostOrder->setShipments($shipmentsPayload);
            $order = $this->easyPostOrder->create();
            $success = $order['success'];
            $message = $order['message'];
            $shippingId = $order['order']['id'];
            $shipments = $order['order']['shipments'];
        } else {
            $epParcel = $epParcels[0]['created'];
            $this->easyPostShipment->setToAddressId($this->toAddressId);
            $this->easyPostShipment->setFromAddressId($this->getFromAddressId());
            $this->easyPostShipment->setReturnAddressId($this->getReturnAddressId());
            $this->easyPostShipment->setParcelId($epParcel['id']);
            $this->easyPostShipment->setType($this->type);

            $this->easyPostShipment->setOptions([
                'saturday_delivery' => $isSaturdayDelivery,
                'print_custom_1' => $order->getOrderId(),
            ]);

            if ($order->isInternational()) {
                $customsInfo = $this->createCustomsInfo($epParcels, $order->getCustomsForm());
                $this->easyPostShipment->setCustomsInfo($customsInfo);
            }

            $shipment = $this->easyPostShipment->create();
            if (!$shipment['success']) {
                return $shipment;
            }
            $success = $shipment['success'];
            $message = $shipment['message'];
            $shippingId = $shipment['shipment']['id'];
            $shipments = [$shipment['shipment']];
        }
        return [
            'success' => $success,
            'message' => $message,
            'shippingId' => $shippingId,
            'parcels' => $epParcels,
            'shipments' => $shipments,
        ];
    }

    public function createCustomsInfo(array $parcels, array $customsForm): array
    {
        if (!$customsForm || count($customsForm) <= 1) {
            return [
                'success' => false,
                'message' => 'Customs form is required for international shipments',
            ];
        }

        $this->easyPostCustoms->setCustomSigner($customsForm['customsSigner']);
        $this->easyPostCustoms->setEelPfc($customsForm['eelPfc']);
        $this->easyPostCustoms->setNonDeliveryOption($customsForm['nonDeliveryAction']);
        $this->easyPostCustoms->setContentsType($customsForm['contentType']);
        $this->easyPostCustoms->setParcels($parcels);

        return $this->easyPostCustoms->create(true);
    }

    public function getRates(string $shipmentOrOrderId): array
    {
        $isOrder = str_contains($shipmentOrOrderId, 'order_');
        if ($isOrder) {
            return $this->easyPostOrder->getRates($shipmentOrOrderId);
        }
        return $this->easyPostShipment->getRates($shipmentOrOrderId);
    }

    public function setToAddressId(?string $toAddressId): void
    {
        $this->toAddressId = $toAddressId;
    }

    public function setType(OrderShipmentTypeEnum $type): void
    {
        $this->type = $type;
    }
}