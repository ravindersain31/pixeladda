<?php

namespace App\Service;

use App\Entity\Admin\Coupon;
use App\Entity\AppUser;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderMessage;
use App\Entity\Store;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Helper\ShippingChartHelper;
use App\Repository\StoreDomainRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Service\StoreInfoService;

class OrderService
{
    private Cart $cart;

    private array $store;

    private Order|null $order;

    private float $subTotalAmount = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService            $userService,
        private readonly Security               $security,
        private readonly OrderLogger            $orderLogger,
        private readonly ShippingChartHelper    $shippingChartHelper,
        private readonly RequestStack           $requestStack,
        private readonly StoreDomainRepository  $storeDomainRepository,
        private readonly StoreInfoService       $storeInfoService,
    )
    {
        $this->order = null;
    }

    public function getOrder(): ?Order
    {
        $this->throwExceptionIfOrderNotStarted();
        return $this->order;
    }

    public function startOrder(Cart $cart, array $store, Order|null $order = null): Order
    {

        $this->cart = $cart;
        $this->store = $store;

        $this->throwExceptionIfStoreNotProvided();

        $this->order = $order;
        if (!$order) {
            $this->order = new Order();
            $this->order->setOrderId($this->generateOrderId());
            $this->order->setStatus(OrderStatusEnum::CREATED);
            $this->order->setPaymentStatus(PaymentStatusEnum::INITIATED);
            $this->order->setCart($cart);
        }
        $this->mapStore($store);
        $this->setStoreDomain($store);
        return $this->order;
    }

    public function setPaymentMethod(string $paymentMethod): ?Order
    {
        $this->throwExceptionIfOrderNotStarted();
        $this->order->setPaymentMethod($paymentMethod);
        $this->orderLogger->setOrder($this->order);
        $this->orderLogger->log('Processing the Payment with ' . $paymentMethod);
        return $this->order;
    }


    public function setAddress(array $billing, array $shipping): ?Order
    {
        if (!$this->order instanceof Order) return null;

        $this->order->setBillingAddress($billing);
        $this->order->setShippingAddress($shipping);
        return $this->order;
    }

    public function setItems(Collection $items): ?Order
    {
        $this->throwExceptionIfOrderNotStarted();

        /** @var CartItem $item */
        foreach ($items as $item) {
            $data = $item->getData();
            $orderItem = new OrderItem();
            $orderItem->setProduct($item->getProduct());
            $orderItem->setQuantity($item->getQuantity());
            $orderItem->setPrice($data['price']);
            $orderItem->setAddOnsAmount($data['unitAddOnsAmount']);
            $orderItem->setUnitAmount($data['unitAmount']);
            $orderItem->setTotalAmount($data['totalAmount']);
            $orderItem->setAddOns($data['addons']);
            $orderItem->setCanvasData($item->getCanvasData());
            $orderItem->setShipping($item->getShipping());
            $orderItem->setCartItemId($item->getId());
            $orderItem->setCartItemId($item->getId());
            $orderItem->setMetaDataKey('additionalNote', $item->getDataKey('additionalNote'));
            $orderItem->setMetaDataKey('isHelpWithArtwork', $item->getDataKey('isHelpWithArtwork'));
            $orderItem->setMetaDataKey('isEmailArtworkLater', $item->getDataKey('isEmailArtworkLater'));
            $orderItem->setMetaDataKey('customSize', $item->getDataKey('customSize'));
            $orderItem->setMetaDataKey('isCustomSize', $item->getDataKey('isCustomSize'));
            $orderItem->setMetaDataKey('deliveryMethod', $item->getDataKey('deliveryMethod'));
            $orderItem->setMetaDataKey('isBlindShipping', $item->getDataKey('isBlindShipping'));
            $orderItem->setMetaDataKey('isFreeFreight', $item->getDataKey('isFreeFreight'));
            $orderItem->setMetaDataKey('isWireStake', $item->getDataKey('isWireStake'));
            $orderItem->setMetaDataKey('isBlankSign', $item->getDataKey('isBlankSign'));
            $orderItem->setMetaDataKey('isSaturdayDelivery', $item->getDataKey('shipping')['isSaturday'] ?? false);
            $orderItem->setMetaDataKey('YSPLogoDiscount', $item->getDataKey('YSPLogoDiscount'));
            $orderItem->setMetaDataKey('customArtwork', $item->getDataKey('customArtwork'));
            $orderItem->setMetaDataKey('customOriginalArtwork', $item->getDataKey('customOriginalArtwork'));
            $orderItem->setMetaDataKey('prePackedDiscount', $item->getDataKey('prePackedDiscount'));
            $orderItem->setMetaDataKey('notes', $item->getDataKey('notes'));

            $shipping = $item->getShipping();
            $orderItem->setDeliveryDate(new \DateTimeImmutable($shipping['date']));
            $this->order->addOrderItem($orderItem);

            $this->subTotalAmount += $orderItem->getTotalAmount();
        }

        return $this->order;
    }

    public function endOrder(): ?Order
    {
        $this->throwExceptionIfOrderNotStarted();

        $this->syncDataWithCart();

        $customerShipping = $this->order->getShippingMetaDataKey('customerShipping');
        if (isset($customerShipping['day']) && $customerShipping['day'] <= 2) {
            $this->order->setIsSuperRush(true);
        }

        $this->entityManager->persist($this->order);
        $this->entityManager->flush();


        $this->orderLogger->setOrder($this->order);
        $this->orderLogger->log('Order has been created with Order Id: ' . $this->order->getOrderId());

        return $this->order;
    }

    private function syncDataWithCart(): void
    {
        $cartData = $this->cart->getData();
        $this->order->setSubTotalAmount($this->subTotalAmount);
        $this->order->setShippingAmount($this->cart->getTotalShipping());
        $this->order->setOrderProtectionAmount($this->cart->getOrderProtectionAmount());
        $this->order->setInternationalShippingChargeAmount($this->cart->getInternationalShippingChargeAmount());
        $this->order->setTotalAmount($this->cart->getTotalAmount());
        $this->order->setDeliveryDate(new \DateTimeImmutable($cartData['deliveryDate']['date']));
        $this->order->setShippingMetaDataKey('customerShipping', $cartData['shipping']);
        $this->addAdditionalDiscounts($cartData);
        $this->order->setCouponDiscountAmount($this->cart->getCouponAmount());
        $this->order->setCoupon($this->cart->getCoupon());
        $this->deductCoupon($this->cart->getCoupon());
        $this->order->setMetaDataKey('deliveryMethod', $cartData['deliveryMethod']);
        $this->order->setMetaDataKey('isBlindShipping', $cartData['isBlindShipping']);
        $this->order->setMetaDataKey('isFreeFreight', $cartData['isFreeFreight']);
        $this->order->setMetaDataKey('isSaturdayDelivery', $cartData['shipping']['isSaturday'] ?? false);

        $needProof = true;
        if ($this->cart->isNeedProof() === false && $this->cart->isDesignApproved() === true) {
            $needProof = false;
        }

        $this->order->setNeedProof($needProof);

        $user = $this->security->getUser() ?: $this->userService->getUserFromAddress($this->order->getBillingAddress());
        $this->order->setUser($user);
    }

    private function mapStore(array $store): void
    {
        $this->order->setStore($this->entityManager->getReference(Store::class, $store['id']));
    }

    public function generateOrderId(): string
    {
        $isPromoStore =  $this->storeInfoService->storeInfo()["isPromoStore"];
        $date = new \DateTimeImmutable();
        $year = $date->format('y');
        $month = $date->format('m');
        $timestampSum = array_sum(str_split($date->getTimestamp()));
        $yspRand = rand(2222, 9999);
        $promoRand = rand(222, 999);
        $rand = $isPromoStore ? $promoRand : $yspRand;
        $yearPart = $isPromoStore ? '3'.$year : $year;
        $orderId = $yearPart . $month . $timestampSum . $rand;
        if ($this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderId])) {
            return $this->generateOrderId();
        }
        return $orderId;
    }

    private function throwExceptionIfOrderNotStarted(): void
    {
        if (!$this->order instanceof Order) throw new Exception('Order not started. Initialize order by calling startOrder(?Order $order) method');
    }

    private function throwExceptionIfStoreNotProvided(): void
    {
        if (!isset($this->store['id'])) {
            throw new Exception('Store not provided');
        }
    }

    private function deductCoupon($coupon): void
    {
        if (!$coupon || empty($coupon)) {
            return;
        }
        $coupon->setUsesTotal($coupon->getUsesTotal() - 1);
        $this->entityManager->persist($coupon);
    }

    public function calculateCouponAmount(Coupon $coupon, float|string $totalAmount = 0): float
    {
        $couponAmount = 0;

        if ($coupon instanceof Coupon) {
            if ($coupon->getType() == "P") {
                $couponAmount = round($totalAmount * $coupon->getDiscount() / 100, 2);
                if ($coupon->getMaximumDiscount() > 0) {
                    $couponAmount = min($couponAmount, $coupon->getMaximumDiscount(), $totalAmount);
                }
            } else if ($coupon->getType() == "F") {
                $couponAmount = round($coupon->getDiscount(), 2);
                if ($coupon->getDiscount() > 0) {
                    $couponAmount = min($couponAmount, $coupon->getDiscount(), $totalAmount);
                }
            }
        }

        return $couponAmount;
    }
    public function addAdditionalDiscounts($cartData){
        // if($this->subTotalAmount >= 1000){
        //     $this->order->setAdditionalDiscountKey(key: 'OFF50',amount: 50, name: "$50 OFF");
        // }
        $logoDiscountText =  $this->storeInfoService->storeInfo()["logoDiscountText"];
        $shipping = $cartData['shipping'];
        $discount = $cartData['shipping']['discount'] ?? null;
        if(isset($shipping, $shipping['discountAmount']) && $shipping['discountAmount'] > 0){
            $this->order->setAdditionalDiscountKey(key: 'shippingDiscount', name: 'Shipping Discount ' . $discount . '% OFF', amount: $cartData['shipping']['discountAmount']);
        }

        if (isset($this->cart->getAdditionalDiscountKey('rewardDiscount')['amount']) && $this->security->getUser()) {
            $this->order->setAdditionalDiscountKey(key: 'rewardDiscount',name: 'YSP Rewards',amount: $this->cart->getAdditionalDiscountKey('rewardDiscount')['amount']);
        }

        if (isset($this->cart->getAdditionalDiscountKey('YSPLogoDiscount')['amount'])) {
            $this->order->setAdditionalDiscountKey(key: 'YSPLogoDiscount',name: $logoDiscountText,amount: $this->cart->getAdditionalDiscountKey('YSPLogoDiscount')['amount']);
        }
        if (isset($this->cart->getAdditionalDiscountKey('prePackedDiscount')['amount'])) {
            $this->order->setAdditionalDiscountKey(key: 'prePackedDiscount', name: 'Yard Letters Discount', amount: $this->cart->getAdditionalDiscountKey('prePackedDiscount')['amount']);
        }

        if (isset($this->cart->getAdditionalDiscountKey(CartPriceManagerService::SUB1500_CODE)['amount'])) {
            $this->order->setAdditionalDiscountKey(
                key: CartPriceManagerService::SUB1500_CODE,
                name: CartPriceManagerService::SUB1500_NAME,
                amount: $this->cart->getAdditionalDiscountKey(CartPriceManagerService::SUB1500_CODE)['amount']
            );
        }
    }

    public function updatePaymentStatus(Order $order): Order
    {
        $balanceAmount = floatVal(bcsub(bcadd($order->getTotalAmount(), $order->getRefundedAmount(), 10), $order->getTotalReceivedAmount(), 10));
        if ($balanceAmount === floatval(0)) {
            $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
        } else if ($balanceAmount < floatval(0) && in_array($order->getPaymentStatus(), [PaymentStatusEnum::PENDING, PaymentStatusEnum::PARTIALLY_REFUNDED])) {
            $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
        } else if ($balanceAmount > 0) {
            $order->setPaymentStatus(PaymentStatusEnum::PENDING);
        }
        return $order;
    }

    /**
     * Clones an order by copying all fields except the orderId.
     *
     * @param Order $order
     * @return Order
     */

    public function cloneOrder(Order $order): Order
    {
        $newOrder = clone $order;
        $newOrder->setOrderId($this->generateOrderId());

        $items = $order->getOrderItems()->map(function (OrderItem $item) use ($newOrder) {
            $newItem = clone $item;
            $newItem->setOrder($newOrder);
            return $newItem;
        });

        $newOrder->setOrderItems($items);

        return $newOrder;
    }

    public function deepCloneOrder(Order $order): Order
    {
        $newOrder = $this->cloneOrder($order);

        $newOrder->setStatus(OrderStatusEnum::RECEIVED);
        if (in_array($newOrder->getOrderChannel(), [OrderChannelEnum::REPLACEMENT, OrderChannelEnum::SM3])) {
            $newOrder->setPaymentStatus(PaymentStatusEnum::COMPLETED);
            $newOrder->setPaymentMethod(PaymentMethodEnum::NO_PAYMENT);
        } else {
            $newOrder->setPaymentStatus(PaymentStatusEnum::PENDING);
            $newOrder->setPaymentMethod(PaymentMethodEnum::SEE_DESIGN_PAY_LATER);
        }

        return $newOrder;
    }

    public function revisionItemCharge(Order $order, ?float $amount = null): void
    {
        $orderItem = new OrderItem();

        if ($amount === null) {
            $amount = OrderStatusEnum::CHARGE_FEE;
        }

        $orderItem->setQuantity(1);
        $orderItem->setPrice($amount);
        $orderItem->setUnitAmount($amount);
        $orderItem->setTotalAmount($amount);
        $orderItem->setItemType(OrderItem::CHARGED_ITEM);

        $proofCount = $order->countOrderMessagesByType(OrderStatusEnum::PROOF);

        $orderItem->setItemName('Additional Revision Fee. Proof #' . ($proofCount) . '');
        $orderItem->setItemDescription('Charged for revision request beyond the allowed free limit. Proof #' . ($proofCount) . '');

        $order->setTotalAmount($order->getTotalAmount() + $orderItem->getTotalAmount());
        $order->setSubTotalAmount($order->getSubTotalAmount() + $orderItem->getTotalAmount() );

        $order->addOrderItem($orderItem);

        $order = $this->updatePaymentStatus($order);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function setStoreDomain(): ?Order
    {
        if (!$this->order || !$this->requestStack->getCurrentRequest()?->getHost()) {
            return null;
        }
    
        $host = $this->requestStack->getCurrentRequest()->getHost();
        $storeDomain = $this->storeDomainRepository->findOneBy(['domain' => $host]);
    
        $this->order->setStoreDomain($storeDomain ?: null);

        return $this->order;
    }

    public function removeFreeFreight(Order $order): void
    {

        foreach ($order->getOrderItems() as $item) {
            $metaData = $item->getMetaData();
            $metaData['isFreeFreight'] = false;
            $item->setMetaData($metaData);
        }

        $this->entityManager->flush();
    }

    public function removeBlindShipping(Order $order): void
    {
        $orderMeta = $order->getMetaData();

        if (($orderMeta['isBlindShipping'] ?? false) === false) {
            return;
        }

        $orderMeta = $order->getMetaData();
        $orderMeta['isBlindShipping'] = false;
        $order->setMetaData($orderMeta);
        
        foreach ($order->getOrderItems() as $item) {
            $metaData = $item->getMetaData();
            $metaData['isBlindShipping'] = false;
            $item->setMetaData($metaData);
        }

        $this->entityManager->flush();
    }

    public function removeRequestPickup(Order $order): void
    {
        $orderMeta = $order->getMetaData();
        if (
            isset($orderMeta['deliveryMethod']['key']) &&
            $orderMeta['deliveryMethod']['key'] === 'REQUEST_PICKUP'
        ) {
            unset($orderMeta['deliveryMethod']);
            $order->setMetaData($orderMeta);
        }
        
        foreach ($order->getOrderItems() as $item) {
            $metaData = $item->getMetaData();

            if (
                isset($metaData['deliveryMethod']) &&
                isset($metaData['deliveryMethod']['key']) &&
                $metaData['deliveryMethod']['key'] === 'REQUEST_PICKUP'
            ) {
                unset($metaData['deliveryMethod']);
                $item->setMetaData($metaData);
            }
        }

        $this->entityManager->flush();
    }
}