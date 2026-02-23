<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Order;
use App\Service\EasyPost\EasyPostAddress;
use App\Service\EasyPost\EasyPostShipment;
use App\Service\Ups\TimeInTransitPayload;
use App\Service\Ups\UpsTimeInTransitService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(
    name: "AdminUpsGroundTransitTimeComponent",
    template: "admin/warehouse/components/transit-time.html.twig"
)]
class AdminUpsGroundTransitTimeComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Order $order;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EasyPostShipment $easyPostShipment,
        private readonly UpsTimeInTransitService $estimatedDelivery
    ){}

    #[LiveAction]
    public function generateTransitTime(): void
    {
        $this->regenerateTransitTime();
        $this->entityManager->refresh($this->order);
    }

    public function regenerateTransitTime(): string
    {
        $shippingAddress = $this->order->getShippingAddress();

        $zipCode = $shippingAddress['zipcode'];
        if (!$zipCode || (isset($zipCode) && !$zipCode)) {
            return 'No transit time available. Missing zip code.';
        }

        $epAddress = $this->easyPostShipment->getAddress($this->easyPostShipment->getFromAddressId());

        $timeInTransitPayload = new TimeInTransitPayload(
            originCountryCode: 'US',
            originPostalCode: '77479',
            originStateProvince: 'TX',
            originCityName: 'Sugar Land',
            destinationCountryCode: $shippingAddress['country'] ?? '',
            destinationStateProvince: $shippingAddress['state'] ?? '',
            destinationPostalCode: $shippingAddress['zipcode'] ?? '',
            destinationCityName: $shippingAddress['city'] ?? ''
        );

        $daysInTransit = $this->estimatedDelivery->retrieveDaysInTransit($timeInTransitPayload);

        if (!$daysInTransit) {
            return 'No transit time available.';
        }

        $this->order->setMetaDataKey('epDaysInTransit', $daysInTransit);
        $this->entityManager->persist($this->order);
        $this->entityManager->flush();

        $this->entityManager->refresh($this->order);

        return $daysInTransit;
    }
}
