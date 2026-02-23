<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\KlaviyoEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class KlaviyoService extends AbstractController
{
    private const ENDPOINT = 'https://a.klaviyo.com/client/events/';
    private const KLAVIYO_REVISION = '2025-07-15';
    private const DEFAULT_CURRENCY = 'USD';
    private const DEFAULT_DECIMAL_PRECISION = 2;
    
    private string $apiUrl;
    private bool $isProdEnv;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->apiUrl = self::ENDPOINT . '?company_id=' . urlencode($parameterBag->get('KLAVIYO_COMPANY_ID'));
        $this->isProdEnv = $parameterBag->get('KLAVIYO_ENV') === 'production';
    }

    public function addedToCart(Cart $cart): ?string
    {
        if (!$this->isValidCart($cart)) {
            return null;
        }
        return $this->sendCartEvent(KlaviyoEvent::ADDED_TO_CART, $cart);
    }

    public function saveCartDesignQuote(Cart $cart, string $eventName, string $email): ?string
    {
        if (!$this->isValidCart($cart)) {
            return null;
        }
        return $this->sendEvent(
            $eventName,
            $this->buildCartProperties($cart),
            $email ? ['email' => $email] : []
        );
    }

    public function startedCheckout(Order $order): ?string
    {
        if (!$this->isValidOrder($order)) {
            return null;
        }
        return $this->sendOrderEvent(KlaviyoEvent::STARTED_CHECKOUT, $order);
    }

    public function placedOrder(Order $order): ?string
    {
        if (!$this->isValidOrder($order)) {
            return null;
        }
        return $this->sendOrderEvent(KlaviyoEvent::PLACED_ORDER, $order);
    }

    public function cancelledOrder(Order $order): ?string
    {
        if (!$this->isValidOrder($order)) {
            return null;
        }
        return $this->sendOrderEvent(KlaviyoEvent::CANCELLED_ORDER, $order);
    }

    public function fulfilledOrder(Order $order): ?string
    {
        if (!$this->isValidOrder($order)) {
            return null;
        }
        return $this->sendOrderEvent(KlaviyoEvent::FULFILLED_ORDER, $order);
    }

    public function viewedProduct(array $productData): ?string
    {
        $properties = [
            'productId' => $productData['productId'] ?? '',
            'SKU' => $productData['SKU'] ?? '',
            'productName' => $productData['productName'] ?? '',
            'image' => $productData['image'] ?? '',
            'category' => $productData['category'] ?? '',
        ];

        $user = $this->getUser();
  
        return $this->sendEvent(
            KlaviyoEvent::VIEWED_PRODUCT,
            $properties,
            $this->buildUserProfile($user)
        );
    }

    private function sendCartEvent(string $eventName, Cart $cart): ?string
    {
        $user = $this->getUser();
        return $this->sendEvent($eventName, $this->buildCartProperties($cart), $this->buildUserProfile($user));
    }

    private function sendOrderEvent(string $eventName, Order $order): ?string
    {
        $cart = $order->getCart();
        $properties = [
            'orderId' => $order->getOrderId() ?? '',
            'totalQuantity' => $cart->getTotalQuantity() ?? 0,
            'shippingCost' => $this->formatAmount($order->getShippingAmount()),
            'subTotal' => $this->formatAmount($order->getSubTotalAmount()),
            'totalAmount' => $this->formatAmount($order->getTotalAmount()),
            'paymentStatus' => $order->getPaymentStatus() ?? '',
            'mobileAlertNumber' => $order->getTextUpdatesNumber() ?? '',
            'items' => $this->buildItemsProperties($cart->getCartItems()),
        ];

        if (in_array($eventName, [KlaviyoEvent::STARTED_CHECKOUT, KlaviyoEvent::PLACED_ORDER])) {
            $properties['value_currency'] = self::DEFAULT_CURRENCY;
        }

        $value = in_array($eventName, [KlaviyoEvent::PLACED_ORDER, KlaviyoEvent::CANCELLED_ORDER])
            ? $order->getTotalAmount() ?? 0
            : 0;

        $user = $order->getUser() ?? $this->getUser();  

        return $this->sendEvent(
            $eventName,
            $properties,
            $this->buildUserProfile($user),
            $value
        );
    }

    private function buildCartProperties(Cart $cart): array
    {
        return [
            'cartId' => $cart->getCartId() ?? '',
            'totalQuantity' => $cart->getTotalQuantity() ?? 0,
            'totalShipping' => $cart->getTotalShipping() ?? 0,
            'totalAmount' => $cart->getTotalAmount() ?? 0,
            'value_currency' => self::DEFAULT_CURRENCY,
            'items' => $this->buildItemsProperties($cart->getCartItems()),
        ];
    }

    private function buildUserProfile(?AppUser $user): array
    {
        return $user ? [
            'email' => $user->getUsername() ?? '',
            'first_name' => $user->getName() ?? '',
        ] : [];
    }

    private function isValidCart(?Cart $cart): bool
    {
        return $cart instanceof Cart && $cart->getCartId() !== null;
    }

    private function isValidOrder(?Order $order): bool
    {
        return $order instanceof Order 
            && $order->getOrderId() !== null
            && $this->isValidCart($order->getCart());
    }

    private function buildItemsProperties(iterable $cartItems): array
    {
        $items = [];

        foreach ($cartItems as $item) {
            $data = $item?->getData() ?? [];
            $items[] = [
                'productId' => $data['id'] ?? '',
                'SKU' => $data['sku'] ?? '',
                'productName' => $data['name'] ?? '',
                'image' => $data['image'] ?? '',
                'category' => $data['customSize']['category'] ?? '',
            ];
        }

        return $items;
    }

    private function formatAmount(?float $amount): string
    {
        return number_format($amount ?? 0, self::DEFAULT_DECIMAL_PRECISION);
    }

    private function sendEvent(string $eventName, array $properties, array $profile = [], float $value = 0): ?string
    {
        if (!$this->isProdEnv) {
            return null;
        }

        if (empty($profile['email'])) {
            return null;
        }

        $eventPayload = [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'properties' => $properties,
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => ['name' => $eventName]
                        ]
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'attributes' => $profile
                        ]
                    ],
                    'unique_id' => uniqid(),
                    'value' => round($value, self::DEFAULT_DECIMAL_PRECISION),
                ]
            ]
        ];

        return $this->curl($eventPayload);
    }

    private function curl(array $data): ?string
    {
        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "content-type: application/json",
                    "revision: " . self::KLAVIYO_REVISION
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5
            ]);

            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }
}