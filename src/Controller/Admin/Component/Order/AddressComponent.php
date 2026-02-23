<?php

namespace App\Controller\Admin\Component\Order;

use App\Entity\Order;
use App\Enum\OrderShipmentTypeEnum;
use App\Enum\ShippingStatusEnum;
use App\Service\EasyPost\EasyPostAddress;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: "AddressComponent",
    template: "admin/components/order/address.html.twig"
)]
class AddressComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Order $order = null;

    #[LiveProp]
    public ?string $type = null;

    #[LiveProp]
    public ?array $address = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EasyPostAddress        $epAddress,
    )
    {
    }

    #[LiveAction]
    public function validateAddress(): void
    {
        $this->epAddress->setName($this->address['firstName'] . ' ' . $this->address['lastName']);
        $this->epAddress->setStreet1($this->address['addressLine1']);
        $this->epAddress->setStreet2($this->address['addressLine2']);
        $this->epAddress->setCity($this->address['city']);
        $this->epAddress->setState($this->address['state']);
        $this->epAddress->setZip($this->address['zipcode']);
        $this->epAddress->setCountry($this->address['country']);
        $this->epAddress->setResidential(true);
        $this->epAddress->setPhone($this->address['phone']);

        $response = $this->epAddress->create();
        if ($response['success']) {
            $this->order->setMetaDataKey('ep' . ucfirst($this->type), $response['address']);
            $this->entityManager->persist($this->order);
            $this->entityManager->flush();
        } else {
            $this->addFlash('danger', $response['message']);
        }

        $this->entityManager->refresh($this->order);
    }

    #[LiveAction]
    public function validateAddressManual(#[LiveArg()] bool $verify): void
    {
        $address = $this->epAddress;

        $shippingAddress = $this->order->getShippingAddress();
        $address->setName($shippingAddress['firstName'] . ' ' . $shippingAddress['lastName']);
        $address->setStreet1($shippingAddress['addressLine1']);
        $address->setStreet2($shippingAddress['addressLine2']);
        $address->setCity($shippingAddress['city']);
        $address->setState($shippingAddress['state']);
        $address->setZip($shippingAddress['zipcode']);
        $address->setCountry($shippingAddress['country']);
        $address->setResidential(true);
        $address->setPhone($shippingAddress['phone']);

        $response = $address->create($verify);
        if ($response['success']) {
            if ($this->type === OrderShipmentTypeEnum::DELIVERY) {
                $this->order->setShippingStatus(ShippingStatusEnum::READY_FOR_SHIPMENT);
            }
            $this->order->setMetaDataKey('epShippingAddress', $response['address']);
            $this->entityManager->persist($this->order);
            $this->entityManager->flush();
        } else {
            $this->addFlash('danger', $response['message']);
            $this->addFlash('info', 'Please update the shipping address on the order overview page. You can access it by clicking the "View" button above.');
        }

        $this->entityManager->refresh($this->order);
    }

}
