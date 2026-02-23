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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApplePayController extends AbstractController
{
    use StoreTrait;

    private ParameterBagInterface $parameterBag;

    #[Route('/apple-pay/initiate', name: 'api_apple_pay_initiate', methods: ['POST'])]
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
        $receivedAmount = $order->getTotalReceivedAmount();
        $refundedAmount = $order->getRefundedAmount();
        $totalAmount = $order->getTotalAmount();

        $dueAmount = round($totalAmount + $refundedAmount - $receivedAmount, 2);

        return $this->paymentData($dueAmount);
    }

    private function createPaymentRequestFromCart(Cart $cart): array
    {
        $lineItems = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $template = $cartItem->getProduct();
            $product = $template->getParent();
            $lineItems[] = [
                'label' => $template->getName() . ' (' . $product->getSku() . ') x ' . $cartItem->getQuantity(),
                'amount' => number_format($cartItem->getDataKey('totalAmount'), 2),
            ];
        }

        if ($cart->getCouponAmount() > 0) {
            $lineItems[] = [
                'label' => 'Coupon Discount',
                'amount' => number_format(-$cart->getCouponAmount(), 2),
            ];
        }

        $additionalDiscount = $cart->getAdditionalDiscount();
        if (is_array($additionalDiscount)) {
            foreach ($additionalDiscount as $key => $discount) {
                if (isset($discount['name'], $discount['amount']) && $discount['amount'] > 0) {
                    $lineItems[] = [
                        'label' => $discount['name'],
                        'amount' => number_format(-$discount['amount'], 2),
                    ];
                }
            }
        }

        if ($cart->getTotalShipping() > 0) {
            $lineItems[] = [
                'label' => 'Shipping',
                'amount' => $cart->getTotalShipping(),
            ];
        }

        if ($cart->getOrderProtectionAmount() > 0) {
            $lineItems[] = [
                'label' => 'Order Protection',
                'amount' => $cart->getOrderProtectionAmount(),
            ];
        }

        return $this->paymentData($cart->getTotalAmount(), $lineItems);
    }

    private function paymentData(string $totalAmount, array $lineItems = []): array
    {
        $data = [
            'merchantName' => 'Vertical Brands',
            'totalPrice' => $totalAmount,
            'currencyCode' => 'USD',
            'countryCode' => 'US', 
            'requiredShippingContactFields' => ["postalAddress", "phone", "email"],
        ];

        if (count($lineItems) > 0) {
            $data['lineItems'] = $lineItems;
        }

        return $data;
    }
}