<?php

namespace App\Service\EasyPost;

use App\Entity\Order;
use EasyPost\EasyPostClient;
use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPostEstimatedDelivery extends Base
{
    use EasyPostAwareTrait;

    private ?string $toAddressId = null;

    private EasyPostShipment $easyPostShipment;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct($parameterBag);
        $this->address = new EasyPostAddress($parameterBag);
        $this->easyPostShipment = new EasyPostShipment($parameterBag);
    }

    public function retrieveEstimatedDeliveryDate(string $toZip, string $deliveryDate, array $carriers = ['UPS']): string
    {
        $fromAddress = $this->getAddressFromAddressId($this->getFromAddressId());

        $params = [
            'from_zip' => $fromAddress['zip'],
            'to_zip' => $toZip,
            'carriers' => $carriers,
            'desired_delivery_date' => $deliveryDate,
        ];

        return $this->easyPostShipment->getEstimatedDeliveryDate(params: $params, deliveryDate: $deliveryDate);

    }

    public function retrieveDaysInTransit(string $toZip, string $deliveryDate, string $service = 'ground', array $carriers = ['UPS']): ?string
    {
        $fromAddress = $this->getAddressFromAddressId($this->getFromAddressId());

        $params = [
            'from_zip' => $fromAddress['zip'],
            'to_zip' => $toZip,
            'carriers' => $carriers,
            'desired_delivery_date' => $deliveryDate,
        ];

        return $this->easyPostShipment->getDaysInTransit(params: $params, service: $service);

    }

    private function getAddressFromAddressId(string $addressId): array
    {
        return $this->easyPostShipment->getAddress($addressId);
    }

    public function setToAddressId(?string $toAddressId): void
    {
        $this->toAddressId = $toAddressId;
    }

}