<?php

namespace App\Controller\Api;


use App\Entity\OrderShipment;
use App\Event\OrderDeliveredEvent;
use App\Event\OrderOutForDeliveryEvent;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EasyPostController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/easy-post/webhook', name: 'easy_post_webhook')]
    public function easyPostWebhook(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);


        if (str_contains($data['description'], 'tracker')) {
            $this->handleTracking($entityManager, $data);
        }

        return $this->json([
            'status' => 'ok',
        ]);
    }

    private function handleTracking(EntityManagerInterface $entityManager, array $data): void
    {
        $tracking = $data['result'];
        if ($tracking['object'] === 'Tracker') {
            $shipmentId = $tracking['shipment_id'];
            $trackingNumber = $tracking['tracking_code'];
            $shipment = $entityManager->getRepository(OrderShipment::class)->findOneBy(['shipmentId' => $shipmentId, 'trackingId' => $trackingNumber]);
            if ($shipment instanceof OrderShipment) {
                $order = $shipment->getOrder();
                $shipment->setStatus($tracking['status']);
                $shipment->setTracking($tracking['tracking_details']);
                $shipment->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->persist($shipment);
                $entityManager->flush();
                if ($shipment->getStatus() === 'out_for_delivery') {
                    $event = new OrderOutForDeliveryEvent($order, $shipment);
                    $this->eventDispatcher->dispatch($event, OrderOutForDeliveryEvent::NAME);
                } else if ($shipment->getStatus() === 'delivered') {
                    $event = new OrderDeliveredEvent($order, $shipment);
                    $this->eventDispatcher->dispatch($event, OrderDeliveredEvent::NAME);
                }

                $this->updateLinkedShipments($entityManager, $shipment);
            }

        }
    }

    private function updateLinkedShipments(EntityManagerInterface $entityManager, OrderShipment $shipment): void
    {
        $linkedShipments = $entityManager->getRepository(OrderShipment::class)->findBy(['order' => $shipment->getOrder()]);
        foreach ($linkedShipments as $linkedShipment) {
            if ($linkedShipment->getTrackingId() !== $shipment->getTrackingId()) {
                // Skip the linked shipment that is the same as the current shipment
                continue;
            }
            $linkedShipment->setStatus($shipment->getStatus());
            $linkedShipment->setTracking($shipment->getTracking());
            $linkedShipment->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($linkedShipment);
        }
        $entityManager->flush();
    }


}