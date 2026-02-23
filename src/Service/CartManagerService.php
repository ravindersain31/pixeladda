<?php

namespace App\Service;

use App\Entity\Admin\Coupon;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\ShippingCharge;
use App\Entity\Store;
use App\Enum\CouponTypeEnum;
use App\Helper\ProductConfigHelper;
use App\Helper\UniqueIdGenerator;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartManagerService extends AbstractController
{

    private array $store;
    private ?SessionInterface $session = null;
    public const INTERNATIONAL_SHIPPING_CHARGE = 75;

    public function __construct(
        RequestStack                             $requestStack,
        private readonly EntityManagerInterface  $entityManager,
        private readonly CartPriceManagerService $priceManagerService,
        private readonly ProductRepository       $productRepository,
        private readonly CartRepository          $repository,
        private readonly CartItemRepository      $itemRepository,
        private readonly UniqueIdGenerator       $idGenerator,
        private readonly SaveDesignService       $saveDesignService,
        private readonly OrderQuoteService       $orderQuoteService,
        private readonly ProductConfigHelper     $productConfigHelper
    )
    {
        $request = $requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $this->store = $request->get('store') ?? [];
            $this->session = $request->getSession();
        }
    }

    public function updateCart(string|null|Cart $cart, array $cartData, ?string $mode = null): Cart
    {
        $items = $cartData['items'] ?? [];
        if (!$cart instanceof Cart) {
            $cart = $this->getCart($cart, false);
        }

        if ($mode && $mode === 'add-to-cart') {
            $cart->setNeedProof(true);
            $cart->setDesignApproved(false);
        }

        $cart->setData($cartData);
        $this->repository->save($cart, true);

        $additionalData = $cartData['additionalData'] ?? [];
        $savedCartItems = [];
        foreach ($items as $item) {
            if ($item['quantity'] > 0) {
                $cartItem = $this->updateItem($cart, $item);
                if (isset($additionalData['saveDesignEmail'])) {
                    array_push($savedCartItems, $cartItem);
                } else if (isset($additionalData['orderQuoteEmail'])) {
                    $this->orderQuote(cart: $cart, cartItem: $cartItem, email: $additionalData['orderQuoteEmail']);
                } else {
                    $cart->addCartItem($cartItem);
                }
            }
        }

        if(isset($additionalData['saveDesignEmail'])) {
            $this->saveDesign(cart: $cart, cartItems: $savedCartItems, email: $additionalData['saveDesignEmail']);
        }

        return $this->refresh($cart);
    }


    public function createShareCart(array $cartData): Cart
    {
        $cart = $this->getShareCart(null);
        $cart->setData($cartData);
        $items = array_values($cartData['items'] ?? []); 
        foreach ($items as $item) { 
            $cartItem = $this->updateItem($cart, $item); 
            $cart->addCartItem($cartItem);
        }
        $this->repository->save($cart, true);

        return $this->refresh($cart);
          
    }

    public function applyCoupon(Cart $cart, string $code): void
    {
        $coupon = $this->entityManager->getRepository(Coupon::class)->findCouponByCode($code);
        if ($coupon instanceof Coupon) {
            $cart->setCoupon($coupon);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
            $this->updateCoupon($cart);
            $this->refresh($cart);
        }
    }

    public function updateItem(string|null|Cart $cart, array $itemData): CartItem
    {
        if (!$cart instanceof Cart) {
            $cart = $this->getCart($cart);
        }
        $cartData = $cart->getData();
        $cartItem = $this->itemRepository->findOneBy(['cart' => $cart, 'itemId' => $itemData['itemId']]);
        unset($itemData['itemId']);
        if (!$cartItem instanceof CartItem) {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setItemId($this->generateNewItemId());
            $cartItem->setProduct($this->productRepository->find($itemData['productId']));
        }
        $cartItem->setQuantity($itemData['quantity']);
        $cartItem->setCanvasData($itemData['canvasData'] ?? []);
        // prevent saving canvas data twice;
        unset($itemData['canvasData']);

        if (isset($itemData['cloneData'])) {
            $cartItem->setData($itemData['cloneData']);
        } else {
            $cartItem->setData($itemData);
        }
        $shipping = [];
        if (isset($cartData['shipping'])) {
            $shipping = $cartData['shipping'];
        } else if (isset($itemData['shipping'])) {
            $shipping = $itemData['shipping'];
        }
        $cartItem->setShipping($shipping);
        if (isset($cartData['deliveryMethod'])) {
            $cartItem->setDatakey('deliveryMethod', $cartData['deliveryMethod'] ?? null);
        }
        if (isset($cartData['isBlindShipping'])) {
            $cartItem->setDatakey('isBlindShipping', $cartData['isBlindShipping']);
        }
        // if (isset($cartData['isFreeFreight'])) {
        //     $cartItem->setDatakey('isFreeFreight', $cartData['isFreeFreight']);
        // }
        $this->itemRepository->save($cartItem, true);
        return $cartItem;
    }

    public function removeItem(string $cartId, string $itemId): Cart
    {
        $cart = $this->getCart($cartId);
        $cartItem = $this->itemRepository->findOneBy(['cart' => $cart, 'id' => $itemId]);
        if ($cartItem instanceof CartItem) {
            $this->itemRepository->remove($cartItem, true);
        }
        return $this->refresh($cart);
    }

    public function getCart(string|null $cartId = null, bool $refresh = true): Cart
    {
        if (!$cartId) {
            $cartId = $this->getCookie();
        }
        $cart = $this->repository->findOneBy(['cartId' => $cartId]);
        if (!$cart instanceof Cart) {
            $cart = $this->createCart();
        }
        if ($this->getCookie() !== $cart->getCartId()) {
            $this->setCookie($cart->getCartId());
        }
        $this->updateCoupon($cart);
        if (!$refresh) {
            return $cart;
        }
        return $this->refresh($cart);
    }

    public function getShareCart(string|null $cartId = null): Cart
    {
        $cart = $this->repository->findOneBy(['cartId' => $cartId]);

        if (!$cart instanceof Cart) {
            $cart = $this->createCart();
        }
        return $cart;
    }

    public function updateOrderProtection(Cart|string|null $cart): null|bool
    {
        if ($cart === null) return null;
        if (!$cart instanceof Cart) {
            $cart = $this->getCart($cart);
        }
        $isOrderProtected = !$cart->isOrderProtection();

        $cart->setOrderProtection($isOrderProtected);
        $this->session->set('orderProtection', $isOrderProtected);

        $orderProtectionAmount = 0;
        if ($isOrderProtected) {
            $totalAmount = $cart->getDataKey('totalAmount');
            $orderProtectionAmount = round($totalAmount * 15 / 100, 2);
        }

        $cart->setOrderProtectionAmount($orderProtectionAmount);
        $this->repository->save($cart, true);

        $this->refresh($cart);
        return $isOrderProtected;
    }

    public function updateInternationalShippingCharge(Cart|string|null $cart): null|bool
    {
        if ($cart === null) return null;
        if (!$cart instanceof Cart) {
            $cart = $this->getCart($cart);
        }
        $isInternationalShippingCharge = !$cart->isInternationalShippingCharge();
        $cart->setInternationalShippingCharge($isInternationalShippingCharge);
        $this->session->set('internationalShippingCharge', $isInternationalShippingCharge);
        $cart->setInternationalShippingChargeAmount(self::INTERNATIONAL_SHIPPING_CHARGE);
        $this->repository->save($cart, true);
        $this->refresh($cart);
        return $isInternationalShippingCharge;
    }

    public function updateCoupon(Cart|string|null $cart): void
    {
        if (!$cart instanceof Cart) {
            $cart = $this->getCart($cart);
        }

        $currentCoupon = $cart->getCoupon();
        $couponAmount = 0;

        if ($currentCoupon instanceof Coupon && $cart->getCoupon() instanceof Coupon) {
            $coupon = $cart->getCoupon();
            $totalAmount = $cart->getSubTotal();
            $totalQuantity = $cart->getTotalQuantity();

            $minQty = $coupon->getMinimumQuantity() ?? 0;
            $maxQty = $coupon->getMaximumQuantity();

            $isQuantityEligible = $totalQuantity >= $minQty && ($maxQty === null || $totalQuantity <= $maxQty);
            if (!$isQuantityEligible) {
                $cart->setTotalAmount($cart->getTotalAmount() + $cart->getCouponAmount());
                $cart->setCoupon(null);
                $cart->setCouponAmount(0);
                $this->repository->save($cart, true);
                return;
            }
            if ($coupon->getType() == "P") {
                $couponAmount = round($totalAmount * $coupon->getDiscount() / 100, 2);
                if($couponAmount > 50 && $coupon->getCouponType() == CouponTypeEnum::REFERRAL) {
                    $couponAmount = 50;
                }

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


        $cart->setCouponAmount($couponAmount);

        if ($cart->getCartItems()->count() == 0) {
            $cart->setTotalAmount($cart->getTotalAmount() + $cart->getCouponAmount());
            $cart->setCoupon(null);
            $cart->setCouponAmount(0);
        }

        if ($currentCoupon && ($currentCoupon->getEndDate() < new \DateTimeImmutable() || $currentCoupon->getUsesTotal() <= 0)) {
            $cart->setTotalAmount($cart->getTotalAmount() + $cart->getCouponAmount());
            $cart->setCoupon(null);
            $cart->setCouponAmount(0);
        }
        $this->repository->save($cart, true);
    }

    public function refresh(Cart $cart): ?Cart
    {
        if (!$cart->getStore()) {
            if (isset($this->store['id'])) {
                $cart->setStore($this->entityManager->getReference(Store::class, $this->store['id']));
            } else {
                $cart->setStore($this->entityManager->getReference(Store::class, 1));
            }
        }

        $this->repository->save($cart, true);

        return $this->priceManagerService->recalculateCartPrice($cart);
    }

    public function getCartSerialized(Cart|string|null $cart, string|null $itemId = null): null|array
    {
        if ($cart === null) return null;
        if (!$cart instanceof Cart) {
            $cart = $this->getCart($cart);
        }
        $cartData['id'] = $cart->getId();
        $cartData['cartId'] = $cart->getCartId();
        $cartData['subTotal'] = $cart->getSubTotal();
        $cartData['totalAmount'] = $cart->getTotalAmount();
        $cartData['totalShipping'] = $cart->getTotalShipping();
        $cartData = [...$cartData, ...$cart->getData()];
        $cartData['items'] = $cart->getCartItems()->filter(function (CartItem $cartItem) use ($itemId) {
            if ($itemId) {
                return intval($cartItem->getId()) === intval($itemId);
            }
            return true;
        })->map(fn(CartItem $item) => [
            'id' => $item->getId(),
            'itemId' => $item->getItemId(),
            'productId' => $item->getProduct()->getId(),
            'quantity' => $item->getQuantity(),
            'data' => $item->getData(),
            'canvasData' => $item->getCanvasData(),
        ])->toArray();
        if ($itemId && count($cartData['items']) > 0) {
            $item = reset($cartData['items']);
            $itemData = $item['data'];

            if (isset($item['data']['shipping'])) {
                $cartData['shipping'] = $item['data']['shipping'];
            }
            if (isset($itemData['addons']) && is_array($itemData['addons'])) {
                foreach ($itemData['addons'] as $addonKey => $addonValue) {
                    if (is_array($addonValue) && isset($addonValue['key'])) {
                        $cartData[$addonKey] = $addonValue['key'];
                    }
                }
            }
        }
        return $cartData;
    }

    public function issueNewCart(): void
    {
        $cart = $this->createCart();
        $this->setCookie($cart->getCartId());
    }

    private function saveDesign(Cart $cart, array $cartItems, string $email): void
    {
        $newCart = clone $cart;
        $newCart->setCartId($this->generateNewCartId());
        $newCart->setCartItems(new ArrayCollection([]));
        foreach($cartItems as $cartItem){
            $newCartItem = clone $cartItem;
            $newCartItem->setItemId($this->generateNewItemId());
            $newCart->addCartItem($newCartItem);
        }
        $this->entityManager->persist($newCart);
        $this->entityManager->flush();
        $this->priceManagerService->recalculateCartPrice($newCart);
        $this->saveDesignService->save($newCart, $email);
        foreach($cartItems as $cartItem){
            if (!in_array($cartItem->getItemId(), $cart->getCartItems()->map(fn($item) => $item->getItemId())->toArray())) {
                $this->removeItem($cart->getCartId(), $cartItem->getId());
            }
        }
    }
    private function orderQuote(Cart $cart, CartItem $cartItem, $email): void
    {
        $newCart = clone $cart;
        $newCartItem = clone $cartItem;
        $newCart->setCartItems(new ArrayCollection([]));
        $newCart->setCartId($this->generateNewCartId());
        $newCartItem->setCart(null);
        $newCartItem->setItemId($this->generateNewItemId());
        $newCart->addCartItem($newCartItem);
        $this->entityManager->persist($newCart);
        $this->entityManager->flush();
        $this->priceManagerService->recalculateCartPrice($newCart);
        $this->orderQuoteService->save($newCart, $email);
        if (!in_array($cartItem->getItemId(), $cart->getCartItems()->map(fn($item) => $item->getItemId())->toArray())) {
            $this->removeItem($cart->getCartId(), $cartItem->getId());
        }
    }

    public function createCart(): Cart
    {
        $cartId = $this->generateNewCartId();
        $cart = new Cart();
        $cart->setCartId($cartId);
        $cart->setOrderProtection(true);
        $this->session->set('orderProtection', true);
        if (isset($this->store['id'])) {
            $cart->setStore($this->entityManager->getReference(Store::class, $this->store['id']));
        } else {
            $cart->setStore($this->entityManager->getReference(Store::class, 1));
        }
        $this->repository->save($cart, true);
        return $cart;
    }

    private function getCookie(): mixed
    {
        return $_COOKIE['cartId'] ?? null;
    }

    private function setCookie(string $cartId): void
    {
        $expire = (new \DateTimeImmutable('now'))->modify("+7 day");
        setcookie('cartId', $cartId, $expire->getTimestamp(), '/');
    }

    public function generateNewCartId(): string
    {
        $uniqueId = $this->idGenerator->generate();
        $cart = $this->repository->findOneBy(['cartId' => $uniqueId]);
        if ($cart instanceof Cart) {
            return $this->generateNewCartId();
        }
        return $uniqueId;
    }

    public function generateNewItemId(): string
    {
        $uniqueId = $this->idGenerator->generate();
        $uniqueIdExists = $this->itemRepository->findIfItemIdExists($uniqueId);
        if ($uniqueIdExists) {
            return $this->generateNewItemId();
        }
        return $uniqueId;
    }

    public function deepClone(?Cart $cart = null, string|null $itemId = null, bool $isRepeatOrder = false, ?Order $order = null): Cart
    {
        if (!$cart) {
            $cart = $this->getCart();
        }
        $cartItems = $cart->getCartItems();

        $newCart = clone $cart;
        $newCart->setCartId($this->generateNewCartId());
        $newCartItems = $newCart->getCartItems();

        if ($itemId) {
            $cartItem = $newCartItems->filter(fn(CartItem $item) => $item->getItemId() === $itemId);
            $items = $cartItem->toArray();
        } else {
            $items = $newCartItems->toArray();
        }

        $items = (new ArrayCollection($items))->map(function (CartItem $item) use ($isRepeatOrder, $order) {
            $newItem = clone $item;
            $newItem->setItemId($this->generateNewItemId());

            if ($isRepeatOrder && $order) {
                $newItem->setDataKey('additionalNote', $item->getDataKey('additionalNote') . "\n" . 'Make Like: ' . $order->getOrderId());
            }
            return $newItem;
        });

        $newCart->setCoupon(null);
        $newCart->setCouponAmount(0);
        $newCart->setAdditionalDiscount([]);

        $newCart->setCartItems($items);
        $this->priceManagerService->recalculateCartPrice($newCart, isClone: true);

        $this->repository->save($newCart, true);
        return $newCart;
    }

    public function isCartEmpty(): bool
    {
        return count($this->getCart()->getCartItems()) == 0;
    }

    public function validateEditItem(Cart $cart, ?string $itemId): bool|RedirectResponse|array
    {
        if (!$itemId) {
            return true;
        }
        $cartId = $cart->getCartId();
        $cartItem = $cart->getCartItems()->findFirst(fn($key, $item) => $item->getId() === intval($itemId));
        if (!$cartItem instanceof CartItem) {
            $this->addFlash('danger', 'Invalid cart item to edit on editor. Please select one from below');
            return $this->redirectToRoute('cart', ['id' => $cartId]);
        }
        if(!isset($cartItem->getData()['id']) || $cartItem->getDataKey('id') == null){
            $id = $cartItem->getDataKey('isCustomSize') ? $this->productConfigHelper->generateUniqueId() : $cartItem->getDataKey('productId');
            $cartItem->setDataKey('id', $id);
            $this->entityManager->persist($cartItem);
            $this->entityManager->flush();
        }
        return [
            'cartId' => $cartId,
            'itemId' => $itemId,
            'cart' => $cart,
            'item' => $cartItem
        ];
    }

    public function getCartOverview(Cart $cart): array
    {
        return [
            'totalQuantity' => intval($cart->getTotalQuantity()),
            'subTotal' => floatval($cart->getSubTotal()),
            'totalAmount' => floatval($cart->getTotalAmount()),
            'totalShipping' => floatval($cart->getTotalShipping()),
            'orderProtectionAmount' => floatval($cart->getOrderProtectionAmount()),
            'internationalShippingChargeAmount' => floatval($cart->getInternationalShippingChargeAmount()),
            'orderProtection' => boolval($cart->isOrderProtection()),
            'internationalShippingCharge' => boolval($cart->isInternationalShippingCharge()),
            'quantityBySizes' => $cart->getDataKey('quantityBySizes'),
            'totalFrameQuantity' => $cart->getDataKey('totalFrameQuantity'),
        ];
    }

    public function applySub1500Discount(Cart $cart): void
    {
        $discountAmount = $cart->getAdditionalDiscountKey(CartPriceManagerService::SUB1500_CODE)['amount'] ?? 0;

        if ($discountAmount > 0) {
            $prevDiscount = $this->session->get('sub1500_discount_amount', 0);

            if ($discountAmount !== $prevDiscount) {
                $this->addFlash(
                    'success',
                    sprintf("Congratulations you have received $%s worth of free items!", $discountAmount)
                );

                $this->session->set('sub1500_discount_amount', $discountAmount);
            }
        } else {
            $this->session->remove('sub1500_discount_amount');
        }
    }
}