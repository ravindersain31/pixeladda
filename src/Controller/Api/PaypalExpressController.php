<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderReceivedEvent;
use App\Payment\Gateway;
use App\Payment\Paypal\Capture;
use App\Service\CartManagerService;
use App\Service\OrderService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaypalExpressController extends AbstractController
{
    use StoreTrait;

    #[Route('/paypal-express/initiate', name: 'api_paypal_express_initiate', methods: ['POST'])]
    public function create(Gateway $gateway, CartManagerService $cartManagerService, OrderService $orderService): Response
    {
        $cart = $cartManagerService->getCart();

        $order = $orderService->startOrder($cart, $this->store);
        $order->setInternationalShippingChargeAmount(0);

        $orderService->setItems($cart->getCartItems());
        $orderService->setPaymentMethod(PaymentMethodEnum::PAYPAL_EXPRESS);

        $order->setAgreeTerms(true);
        $order->setStatus(OrderStatusEnum::CREATED);

        $order->setPaymentStatus(PaymentStatusEnum::PROCESSING);

        $order = $orderService->endOrder();

        $gateway->initialize($order->getPaymentMethod(), 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $payment = $gateway->startPayment()->execute();

        if ($payment['success'] && $payment['action'] === 'capture') {
            return $this->json([
                'success' => true,
                'message' => 'Order has created successfully. Please complete the payment on Paypal',
                'order' => [
                    'orderId' => $order->getOrderId(),
                    'gatewayId' => $payment['transaction']['gatewayId'],
                ]
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'There was some issues in processing your order. Please try again or contact support.',
        ]);
    }

    #[Route('/paypal-express/response/{orderId}', name: 'api_paypal_express_response', methods: ['POST'])]
    public function update(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, Request $request, Capture $capture, EntityManagerInterface $entityManager, SessionInterface $session, CartManagerService $cartManagerService): Response
    {

        $data = $request->get('data');

        $capture->setOrder($order);
        $capture->setToken($data['paymentID']);
        $capture->setPayerId($data['payerID']);

        $transaction = $entityManager->getRepository(OrderTransaction::class)->findOneBy(['gatewayId' => $data['paymentID']]);
        $capture->setTransaction($transaction);
        $response = $capture->execute(true);
        if ($response['success']) {
            $session->set('newOrder', true);
            $session->set('orderId', $order->getOrderId());
            $cartManagerService->issueNewCart();
            $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);
            // $this->addFlash('success', $response['message']);
            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('order_view', ['oid' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'message' => 'Order has been placed successfully'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'There was some issues in processing your order. Please try again'
        ]);
    }

}