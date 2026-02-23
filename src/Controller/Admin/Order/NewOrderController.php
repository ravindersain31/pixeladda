<?php

namespace App\Controller\Admin\Order;

use App\Controller\Admin\Order\NewOrder\AddOns;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Store;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTagsEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Form\Admin\Order\New\NewOrderType;
use App\Helper\ShippingChartHelper;
use App\Helper\VichS3Helper;
use App\Service\GoogleDriveService;
use App\Service\OrderLogger;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewOrderController extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VichS3Helper           $vichS3Helper,
        private readonly ShippingChartHelper    $shippingChartHelper,
    )
    {
    }

    #[Route('/orders/new', name: 'order_new_order')]
    public function newOrder(Request $request, EntityManagerInterface $entityManager, UserService $userService, OrderLogger $orderLogger, GoogleDriveService $googleDriveService): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = new Order();
        $order->setIsManual(true);
        $form = $this->createForm(NewOrderType::class, $order);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hasErrors = false;
            $orderExists = $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $order->getOrderId()]);
            if (!empty($order->getOrderId()) && $orderExists) {
                $form->get('orderId')->addError(new FormError('Order ID already exists'));
                $hasErrors = true;
            }
            if (!$hasErrors) {
                if (empty($order->getOrderId())) {
                    $order->setOrderId($this->generateOrderId());
                }
                $items = $form->get('items')->getData() ?? [];
                $order = $this->addItemsToOrder($order, $items, $form);

                $order->setStore($this->entityManager->getReference(Store::class, 1));
                $order->setStatus(OrderStatusEnum::RECEIVED);
                if (in_array($order->getOrderChannel(), [OrderChannelEnum::REPLACEMENT, OrderChannelEnum::SM3])) {
                    $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
                    $order->setPaymentMethod(PaymentMethodEnum::NO_PAYMENT);
                } else {
                    $order->setPaymentStatus(PaymentStatusEnum::PENDING);
                    $order->setPaymentMethod(PaymentMethodEnum::SEE_DESIGN_PAY_LATER);
                }
                $orderTag = $form->get('orderTag')->getData();
                $order = $this->buildOrderTags($order, $orderTag);

                $order = $this->applyShipping($order, $form);

                $order = $this->updateTotal($order);

                if ($order->getOrderChannel() === OrderChannelEnum::SALES && $order->getTotalAmount() <= 0) {
                    $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
                    $order->setPaymentMethod(PaymentMethodEnum::NO_PAYMENT);
                }

                $user = $userService->getUserFromAddress($order->getBillingAddress());
                $order->setUser($user);
                $order->setInternationalShippingChargeAmount(0);
                $entityManager->persist($order);
                $entityManager->flush();

                $orderLogger->setOrder($order);
                $message = 'Manual order has been created with Order ID: ' . $order->getOrderId().' and the order channel is '.$order->getOrderChannel()->label();
                if($order->getParent()) {
                    $message .= ' and this is the sub order of Order ID '.$order->getParent()->getOrderId();
                }
                $orderLogger->log($message);

                $driveLink = $googleDriveService->createOrderFolder($order->getOrderId());
                if($driveLink) {
                    $order->setDriveLink($driveLink);
                    $this->entityManager->flush();
                }

                return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
            }
        }

        return $this->render('admin/order/add/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function updateTotal(Order $order): Order
    {
        $items = $order->getOrderItems();
        $subTotal = 0;
        foreach ($items as $item) {
            $subTotal += $item->getTotalAmount();
        }
        $order->setSubTotalAmount($subTotal);

        $totalAmount = $subTotal + $order->getShippingAmount() + $order->getOrderProtectionAmount();
        $order->setTotalAmount($totalAmount);
        return $order;

    }

    private function applyShipping(Order $order, $form): Order
    {
        $shippingDay = $form->get('shippingDate')->getData();

        $quantity = $order->getTotalQuantity();
        $firstItem = $order->getOrderItems()->first();
        $product = $firstItem->getProduct();
        $productType = $product->getParent()->getProductType();
        $shippingChart = $this->shippingChartHelper->build($productType->getShipping());
        $shippingDates = $this->shippingChartHelper->getShippingByQuantity($quantity, $shippingChart);

        $shipping = end($shippingDates);
        $shippingAmount = 0;
        if(isset($shippingDates['day_'.$shippingDay])) {
            $shipping = $shippingDates['day_'.$shippingDay];
            if(!$shippingDates['day_'.$shippingDay]['free']) {
                foreach ($shipping['pricing'] as $price) {
                    if ($quantity >= $price['qty']['from'] && $quantity <= $price['qty']['to']) {
                        $shippingAmount = $price['usd'] ?? 0;
                    }
                }
            }
        }
        $order->setDeliveryDate(new \DateTimeImmutable($shipping['date']));
        $order->setShippingMetaDataKey('customerShipping', $shipping);
        $order->setMetaDataKey('isSaturdayDelivery', $shipping['isSaturday']);
        $order->setShippingAmount($shippingAmount);
        return $order;
    }

    private function buildOrderTags(Order $order, array $orderTags): Order
    {
        $customTags = [];
        foreach (OrderTagsEnum::ALL_TAGS as $key => $name) {
            $customTags[$key] = [
                'name' => $name,
                'active' => in_array($key, $orderTags, true),
            ];
        }
        $order->setMetaDataKey('tags', $customTags);
        $order->setIsFreightRequired($customTags[OrderTagsEnum::FREIGHT]['active']);

        $requestPickup = in_array('REQUEST_PICKUP', $orderTags);
        $blindShipping = in_array('BLIND_SHIPPING', $orderTags);
        $isSaturdayDelivery = in_array('SATURDAY_DELIVERY', $orderTags);
        $freight = in_array('FREIGHT', $orderTags);

        $isSuperRush = in_array('SUPER_RUSH', $orderTags);

        $order->setIsSuperRush($isSuperRush);

        $order->setMetaDataKey('isFreeFreight', $freight);
        $order->setMetaDataKey('isBlindShipping', $blindShipping);
        $order->setMetaDataKey('isSaturdayDelivery', $isSaturdayDelivery);
        $order->setMetaDataKey('deliveryMethod', [
            "key" => $requestPickup ? "REQUEST_PICKUP" : "DELIVERY",
            "type" => "percentage",
            "label" => $requestPickup ? "Request Pickup" : "Delivery",
            "discount" => 0
        ]);

        return $order;
    }

    private function addItemsToOrder(Order $order, array $items, $form): Order
    {
        foreach ($items as $item) {
            if (in_array($order->getOrderChannel(), [OrderChannelEnum::REPLACEMENT, OrderChannelEnum::SM3])) {
                $item['price'] = 0;
            }

            $templateSize = $item['width'] . 'x' . $item['height'];
            $orderItem = new OrderItem();
            $orderItem->setItemName($item['name']);
            $orderItem->setItemType('DEFAULT');
            $orderItem->setOrder($order);
            $orderItem->setCanvasData(['front' => [], 'back' => []]);
            list($product, $variant) = $this->getProduct($item['name'], $templateSize);
            $orderItem->setProduct($variant);

            $addOns = $this->buildAddOns($item);
            $orderItem->setAddOns($addOns);

            $metaData = $this->buildItemMetaData($orderItem, $item, $form);
            $orderItem->setMetaData($metaData);

            $orderItem->setAddOnsAmount(0);
            $orderItem->setPrice(floatval($item['price']));
            $orderItem->setQuantity(intval($item['quantity']));

            $unitAmount = $orderItem->getPrice() + $orderItem->getAddOnsAmount();
            $orderItem->setUnitAmount($unitAmount);

            $totalAmount = $orderItem->getPrice() * $orderItem->getQuantity();
            $orderItem->setTotalAmount($totalAmount);
            $order->addOrderItem($orderItem);
        }

        return $order;
    }

    private function buildItemMetaData(OrderItem $orderItem, $item, $form): array
    {
        $variant = $orderItem->getProduct();
        $product = $variant->getParent();
        $category = $product->getPrimaryCategory();
        $additionalNotes = $form->get('additionalNotes')->getData();
        $orderTag = $form->get('orderTag')->getData();
        $isBlindShipping = in_array('BLIND_SHIPPING', $orderTag);
        $requestPickup = in_array('REQUEST_PICKUP', $orderTag);
        $isSaturdayDelivery = in_array('SATURDAY_DELIVERY', $orderTag);
        return [
            "customSize" => [
                "sku" => $product->getSku(),
                "image" => $this->vichS3Helper->asset($variant, 'imageFile'),
                "category" => $category->getSlug(),
                "productId" => $variant->getId(),
                "isCustomSize" => str_contains($product->getSku(), 'CUSTOM-SIZE'),
                "templateSize" => [
                    "width" => $item['width'],
                    "height" => $item['height']
                ],
                "closestVariant" => $item['width'] . 'x' . $item['height'],
            ],
            "isCustomSize" => str_contains($product->getSku(), 'CUSTOM-SIZE'),
            "additionalNote" => $additionalNotes ?? "",
            "deliveryMethod" => $requestPickup ? [
                "key" => "REQUEST_PICKUP",
                "type" => "percentage",
                "label" => "Request Pickup",
                "discount" => 0
            ] : null,
            "isBlindShipping" => $isBlindShipping,
            'isSaturdayDelivery' => $isSaturdayDelivery,
            "isHelpWithArtwork" => false,
            "isEmailArtworkLater" => false
        ];
    }

    private function buildAddOns(array $item): array
    {
        $baseAdOns = [
            "frame" => AddOns::CONFIG['frame']['NONE'],
            "shape" => AddOns::CONFIG['shape']['SQUARE'],
            "sides" => AddOns::CONFIG['sides']['SINGLE'],
            "grommets" => AddOns::CONFIG['grommets']['NONE'],
            "grommetColor" => AddOns::CONFIG['grommetColor']['SILVER'],
            "imprintColor" => AddOns::CONFIG['imprintColor']['UNLIMITED'],
        ];
        if (isset(AddOns::CONFIG['sides'][$item['sides']])) {
            $baseAdOns['sides'] = AddOns::CONFIG['sides'][$item['sides']];
        }
        if (isset(AddOns::CONFIG['shape'][$item['shapes']])) {
            $baseAdOns['shape'] = AddOns::CONFIG['shape'][$item['shapes']];
        }
        if (isset(AddOns::CONFIG['imprintColor'][$item['imprintColor']])) {
            $baseAdOns['imprintColor'] = AddOns::CONFIG['imprintColor'][$item['imprintColor']];
        }
        if (isset(AddOns::CONFIG['grommets'][$item['grommets']])) {
            $baseAdOns['grommets'] = AddOns::CONFIG['grommets'][$item['grommets']];
        }
        if (isset(AddOns::CONFIG['grommetColor'][$item['grommetColor']])) {
            $baseAdOns['grommetColor'] = AddOns::CONFIG['grommetColor'][$item['grommetColor']];
        }
        if (isset(AddOns::CONFIG['frame'][$item['frame']])) {
            $baseAdOns['frame'] = AddOns::CONFIG['frame'][$item['frame']];
        }
        return $baseAdOns;
    }

    private function getProduct(string $sku, string $templateSize): array
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        if ($product) {
            $variant = $this->entityManager->getRepository(Product::class)->findOneBy(['parent' => $product, 'name' => $templateSize]);
            if ($variant) {
                return [$product, $variant];
            }
        }
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => 'CUSTOM-SIZE/01']);
        return [$product->getParent(), $product];
    }

    private function generateOrderId(int $attempt = 0): string
    {
        if ($attempt > 20) {
            throw new \RuntimeException('Failed to generate a unique order ID after multiple attempts. Please try again');
        }
        $date = new \DateTimeImmutable();
        $year = $date->format('y');
        $month = $date->format('m');
        $timestampSum = array_sum(str_split($date->getTimestamp()));
        $rand = rand(2222, 9999);
        $orderId = $year . $month . $timestampSum . $rand;
        if ($this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId])) {
            return $this->generateOrderId($attempt + 1);
        }
        return $orderId;
    }

}
