<?php

namespace App\Controller\Admin\EasyPost;

use App\Controller\Admin\Component\EasyPostShipmentComponent;
use App\Entity\Order;
use App\Entity\OrderShipment;
use App\Enum\OrderShipmentTypeEnum;
use App\Helper\ParcelGenerator;
use App\Service\EasyPost\PreferredShipping;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShipmentAutomationController extends AbstractController
{
    private Order $order;
    private OrderShipmentTypeEnum $type = OrderShipmentTypeEnum::DELIVERY;
    private ?string $shippingRateId = null;
    private bool $isGroundAvailable = false;

    public function __construct(
        private readonly EasyPostShipmentComponent $easyPostComponent,
        private readonly EntityManagerInterface $entityManager,
        private readonly PreferredShipping $preferredShipping,
    ) {
    }

    #[Route('/easypost/initialize-ep-automation', name: 'easy_post_shipment_automation', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data['orderId'])) {
            return $this->errorResponse('Invalid request data.');
        }

        $this->order = $this->fetchOrder($data['orderId']);
        if (!$this->order) {
            return $this->errorResponse('Order not found.');
        }

        try {
            $this->initializeComponent();
            $existingBatches = $this->getExistingBatchCount();

            $this->ensureValidShippingAddress();

            if ($existingBatches === 0) {
                $this->createParcels();

                if ($this->getExistingBatchCount() !== 1) {
                    return $this->errorResponse('Multiple batches found. Please create/update manually shipment.');
                }

                $this->selectShippingRate();
                if (!$this->shippingRateId) {
                    return $this->errorResponse('No valid shipping rate found. Please create manually.');
                }

                if (!$this->isGroundAvailable) {
                    return $this->errorResponse('Ground shipping rate not available. Please create manually.');
                }

                $this->buyLabel(1);
                $this->printAllLabelInPDF();

            } elseif ($existingBatches >= 1) {
                return $this->errorResponse('Multiple batches found. Please create manually new shipment.');
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Shipment automation initialized successfully.',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    // --- Private helpers below ---

    private function fetchOrder(string $orderId): ?Order
    {
        return $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId]);
    }

    private function initializeComponent(): void
    {
        $this->easyPostComponent->order = $this->order;
        $this->easyPostComponent->type = $this->type;

        $shipment = $this->order->getOrderShipments()->first();
        if ($shipment) {
            $rates = $shipment->getRates();
            $selectedRate = $this->preferredShipping->get($rates, $this->order)['selectedRate'] ?? null;
            $this->shippingRateId = $selectedRate;
            $this->easyPostComponent->shippingRateId = $selectedRate;
        }
    }

    private function getExistingBatchCount(): int
    {
        return $this->entityManager
            ->getRepository(OrderShipment::class)
            ->numberOfBatchExists($this->order, $this->type);
    }

    private function ensureValidShippingAddress(): void
    {
        $epAddress = $this->order->getMetaDataKey('epShippingAddress')['zip'] ?? null;
        if (!$epAddress) {
            $this->validateAddress();
            $this->refreshOrder();
            $epAddress = $this->order->getMetaDataKey('epShippingAddress')['zip'] ?? null;
        }

        if (!$epAddress) {
            $this->errorResponse('Valid shipping address not found. Please provide a valid address or create shipment manually.');
        }
    }

    private function refreshOrder(): void
    {
        $this->order = $this->fetchOrder($this->order->getOrderId());
    }

    private function validateAddress(): void
    {
        $this->easyPostComponent->validateAddress(true);
    }

    private function createParcels(): void
    {
        $parcelGenerator = new ParcelGenerator();
        $groupedItems = $this->order->groupedItemsQtyBySizes();
        $parcels = $parcelGenerator->generateDefaultParcels($groupedItems);

        $this->easyPostComponent->createParcels($parcels);
    }

    private function selectShippingRate(): void
    {
        $this->refreshOrder();
        $shipment = $this->order->getOrderShipments()->first();
        if (!$shipment)
            return;

        $rates = $this->order->getOrderShipments()->first()->getRates() ?? [];
        foreach ($rates as $rate) {
            if ($rate['carrier'] === 'UPS' && $rate['service'] === 'Ground' || $rate['carrier'] === 'UPSDAP' && $rate['service'] === 'Ground') {
                $this->shippingRateId = $rate['id'];
                $this->easyPostComponent->shippingRateId = $rate['id'];
                $this->isGroundAvailable = true;
                break;
            }
        }
    }

    private function buyLabel(int $batch): void
    {
        $this->easyPostComponent->buyLabel($batch);
    }

    private function printAllLabelInPDF(): void
    {
        // Disable deprecation and notice warnings
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

        $this->easyPostComponent->printAllLabelInPDF();

        // Enable deprecation and notice warnings
        error_reporting(E_ALL);
    }

    private function errorResponse(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_OK);
    }
}