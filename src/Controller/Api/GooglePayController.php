<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Service\CartManagerService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GooglePayController extends AbstractController
{
    use StoreTrait;

    private ParameterBagInterface $parameterBag;

    #[Route('/google-pay/initiate', name: 'api_google_pay_initiate', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, CartManagerService $cartManagerService, ParameterBagInterface $parameterBag): Response
    {
        $this->parameterBag = $parameterBag;
        $action = $request->get('action');
        $cart = $cartManagerService->getCart();
        $requestData = [];
        if ($action === 'checkout') {
            $requestData = $this->createPaymentRequestFromCart($cart);
        } else if ($action === 'payment_link') {
            $orderId = $request->get('orderId');
            $order = $entityManager->getRepository(Order::class)->findOneBy([
                'orderId' => $orderId,
                'store' => $this->getStore()->id,
            ]);
            $paymentLink = $request->get('paymentLink');
            $transaction = $entityManager->getRepository(OrderTransaction::class)->findOneBy([
                'order' => $order,
                'transactionId' => $paymentLink,
            ]);
            if ($transaction instanceof OrderTransaction) {
                $requestData = $this->createPaymentRequestFromTransaction($transaction);
            }
        } else if ($action === 'approve_proof') {
            $orderId = $request->get('orderId');
            $order = $entityManager->getRepository(Order::class)->findOneBy([
                'orderId' => $orderId,
                'store' => $this->getStore()->id,
            ]);
            if ($order instanceof Order) {
                $requestData = $this->createPaymentRequestFromOrder($order);
            }
        }
        return $this->json([
            'success' => true,
            'requestData' => $requestData,
        ]);
    }

    private function createPaymentRequestFromTransaction(OrderTransaction $transaction): array
    {
        return $this->paymentData($transaction->getAmount());
    }

    private function createPaymentRequestFromOrder(Order $order): array
    {
        $displayItems = [];

        foreach ($order->getOrderItems() as $orderItem) {
            $template = $orderItem->getProduct();
            $product = $template->getParent();
            $displayItems[] = [
                'label' => $template->getName() . ' (' . $product->getSku() . ') x ' . $orderItem->getQuantity(),
                'type' => 'LINE_ITEM',
                'price' => $orderItem->getTotalAmount(),
            ];
        }

        if ($order->getCouponDiscountAmount() > 0) {
            $displayItems[] = [
                'label' => 'Coupon Discount',
                'type' => 'LINE_ITEM',
                'price' => -$order->getCouponDiscountAmount(),
            ];
        }

        if ($order->getShippingAmount() > 0) {
            $displayItems[] = [
                'label' => 'Shipping',
                'type' => 'LINE_ITEM',
                'price' => $order->getShippingAmount(),
            ];
        }

        if ($order->getOrderProtectionAmount() > 0) {
            $displayItems[] = [
                'label' => 'Order Protection',
                'type' => 'LINE_ITEM',
                'price' => $order->getOrderProtectionAmount(),
            ];
        }

        $displayItems[] = [
            'label' => 'Subtotal',
            'type' => 'SUBTOTAL',
            'price' => $order->getSubTotalAmount(),
        ];

        return $this->paymentData($order->getTotalAmount(), $displayItems);
    }

    private function createPaymentRequestFromCart(Cart $cart): array
    {
        $displayItems = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $template = $cartItem->getProduct();
            $product = $template->getParent();
            $displayItems[] = [
                'label' => $template->getName() . ' (' . $product->getSku() . ') x ' . $cartItem->getQuantity(),
                'type' => 'LINE_ITEM',
                'price' => number_format($cartItem->getDataKey('totalAmount'), 2),
            ];
        }

        if ($cart->getCouponAmount() > 0) {
            $displayItems[] = [
                'label' => 'Coupon Discount',
                'type' => 'LINE_ITEM',
                'price' => number_format(-$cart->getCouponAmount(), 2),
            ];
        }

        if ($cart->getTotalShipping() > 0) {
            $displayItems[] = [
                'label' => 'Shipping',
                'type' => 'LINE_ITEM',
                'price' => $cart->getTotalShipping(),
            ];
        }

        if ($cart->getOrderProtectionAmount() > 0) {
            $displayItems[] = [
                'label' => 'Order Protection',
                'type' => 'LINE_ITEM',
                'price' => $cart->getOrderProtectionAmount(),
            ];
        }

        $displayItems[] = [
            'label' => 'Subtotal',
            'type' => 'SUBTOTAL',
            'price' => $cart->getSubTotal(),
        ];

        return $this->paymentData($cart->getTotalAmount(), $displayItems);
    }

    private function paymentData(string $totalAmount, array $displayItems = []): array
    {
        $data = [
            // 'shippingAddressRequired' => true,
            // 'emailRequired' => true,
            'callbackIntents' => ['PAYMENT_AUTHORIZATION'],
            'merchantInfo' => [
                'merchantName' => 'Vertical Brands',
                'merchantId' => $this->parameterBag->get('GOOGLE_PAY_MERCHANT_ID'),
            ],
            'transactionInfo' => [
                // https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo
                // 'countryCode' => 'US', // TODO: get from store 'region'
                'currencyCode' => 'USD',
                'totalPriceStatus' => 'FINAL',
                'totalPrice' => $totalAmount,
                'totalPriceLabel' => 'Total',
            ]
        ];

        if (count($displayItems) > 0) {
            $data['transactionInfo']['displayItems'] = $displayItems;
        }
        return $data;
    }

}