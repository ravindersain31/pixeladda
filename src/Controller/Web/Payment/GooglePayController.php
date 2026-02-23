<?php

namespace App\Controller\Web\Payment;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderProofApprovedEvent;
use App\Event\OrderReceivedEvent;
use App\Form\CheckoutType;
use App\Payment\Gateway;
use App\Payment\Paypal\Capture;
use App\Service\CartManagerService;
use App\Service\OrderService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GooglePayController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/payment/google-pay/checkout', name: 'google_pay_checkout')]
    public function paypalReturn(Request $request, Gateway $gateway, OrderService $orderService, SessionInterface $session, CartManagerService $cartManagerService): Response
    {
        $email = $request->get('email');
        $address = $request->get('shippingAddress');
        $paymentNonce = $request->get('paymentNonce');

        $billingAddress = $this->makeOrderAddress($address, $email);
        $shippingAddress = $this->makeOrderAddress($address, $email);

        $cart = $cartManagerService->getCart();

        $order = $orderService->startOrder($cart, $this->store);
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
        $order->setPaymentMethod(PaymentMethodEnum::GOOGLE_PAY);
        $order->setAgreeTerms(true);
        $order->setTextUpdates(true);
        $order->setTextUpdatesNumber($billingAddress['phone']);

        $orderService->setItems($cart->getCartItems());
        $order = $orderService->endOrder();

        $gateway->initialize(PaymentMethodEnum::GOOGLE_PAY, 'USD');
        $gateway->setOrder($order);
        $gateway->setStore($this->store);
        $gateway->setPaymentNonce($paymentNonce);
        $payment = $gateway->startPayment()->execute();
        if ($payment['success']) {
            $cartManagerService->issueNewCart();
            $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, true), OrderReceivedEvent::NAME);
            $session->set('newOrder', true);
            $session->set('orderId', $order->getOrderId());
            return $this->json([
                'success' => true,
                'message' => 'Order created successfully',
                'action' => 'redirect',
                'redirectUrl' => $this->generateUrl('order_view', ['oid' => $order->getOrderId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => $payment['message'],
            'action' => 'showMessage',
        ]);
    }

    private function makeOrderAddress(array $googleAddress, string $email): array
    {
        $name = $this->convertFullNameToFirstNameLastName($googleAddress['name']);

        return [
            'firstName' => $name[0] ?? '',
            'lastName' => $name[1] ?? '',
            'addressLine1' => $googleAddress['address1'],
            'addressLine2' => $googleAddress['address2'] . ' ' . $googleAddress['address3'],
            'city' => $googleAddress['locality'],
            'state' => $googleAddress['administrativeArea'],
            'country' => $googleAddress['countryCode'],
            'zipcode' => $googleAddress['postalCode'],
            'email' => $email,
            'phone' => $googleAddress['phoneNumber'],
        ];
    }

    private function convertFullNameToFirstNameLastName(string $fullName): array
    {
        $parts = explode(" ", $fullName);
        if (count($parts) > 1) {
            $lastname = array_pop($parts);
            $firstname = implode(" ", $parts);
        } else {
            $firstname = $fullName;
            $lastname = " ";
        }
        return [$firstname, $lastname];
    }
}