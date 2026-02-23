<?php

namespace App\Controller\Web;

use App\Enum\PaymentMethodEnum;
use App\Enum\ShippingEnum;
use App\Event\OrderReceivedEvent;
use App\Form\CheckoutType;
use App\Payment\AmazonPay\AmazonPay;
use App\Payment\Gateway;
use App\Service\AddressService;
use App\Service\CartManagerService;
use App\Service\KlaviyoService;
use App\Service\OrderService;
use App\Service\RecaptchaManager;
use App\Service\SavedPaymentDetailService;
use App\Trait\StoreTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/checkout', name: 'checkout')]
    public function checkout(
        Request $request,
        CartManagerService $cartManagerService,
        OrderService $orderService,
        Gateway $gateway,
        Session $session,
        KlaviyoService $klaviyoService,
        AmazonPay $amazonPay,
        RateLimiterFactory $recaptchaFailuresLimiter,
        RecaptchaManager $recaptchaManager,
        AddressService $addressService,
        SavedPaymentDetailService $savedPaymentDetailService,
    ): Response {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $showRecaptcha = $recaptchaManager->shouldShowRecaptcha($request, $recaptchaFailuresLimiter);
        $cart = $cartManagerService->getCart();
        $internationalShippingCharge = $cart->isInternationalShippingCharge();
        if ($internationalShippingCharge === false) {
            $totalAmount = $cart->getTotalAmount() - $cart->getInternationalShippingChargeAmount();
            $cart->setTotalAmount($totalAmount);
            $cart->setInternationalShippingChargeAmount(0);
        }
        if ($cartManagerService->isCartEmpty()) {
            return $this->redirectToRoute('cart');
        }
        $internationalShippingCharge = ShippingEnum::INTERNATIONAL_SHIPPING_CHARGE;
        
        $order = $orderService->startOrder($cart, $this->store);
        $klaviyoService->startedCheckout($order);
        $totalAmount = $cart->getTotalAmount() ?? 0;
        $form = $this->createForm(CheckoutType::class, $order, [
            'totalAmount' => $totalAmount,
            'showRecaptcha' => $showRecaptcha,
            'user' => $this->getUser(),
            'cart' => $cart,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');
            $paymentMethod = $form->get('paymentMethod')->getData();
            $paymentNonce = $form->get('paymentNonce')->getData();
            $existingCardToken = null;
            $saveNewCard = false;
            $newSavedToken = null;

            if ($form->has('savedCardToken')) {
                $existingCardToken = $form->get('savedCardToken')->getData();
            }

            if ($form->has('saveCard')) {
                $saveNewCard = (bool) $form->get('saveCard')->getData();
            }

            if ($paymentMethod === PaymentMethodEnum::CREDIT_CARD && empty($paymentNonce) && empty($existingCardToken)) {
                $form->get('paymentMethod')->addError(new FormError('Please enter the valid payment details. If this error persists contact support.'));
            } else {

                if ($form->has('addToSavedAddress') && $form->get('addToSavedAddress')->getData()) {
                    $shippingData = $form->get('shippingAddress')->getData();

                    if (is_array($shippingData)) {
                        $addressService->saveAddress(
                            ['shippingAddress' => $shippingData],
                            $this->getUser()
                        );
                    }
                }

                if (
                    $paymentMethod === PaymentMethodEnum::CREDIT_CARD
                    && $paymentNonce
                    && !$existingCardToken
                    && $saveNewCard
                    && $this->getUser()
                ) {
                    $savedcard = $savedPaymentDetailService->add(
                        $this->getUser(),
                        $paymentNonce
                    );

                    if ($savedcard['success']) {
                        $newSavedToken = $savedcard['data']['token'];
                    }
                }
    
                $orderService->setItems($cart->getCartItems());
                $order = $orderService->endOrder();

                $gateway->initialize($paymentMethod, 'USD');
                $gateway->setOrder($order);
                $gateway->setStore($this->store);
                
                if ($existingCardToken) {
                    $gateway->setSavedCardToken($existingCardToken);
                } elseif ($newSavedToken) {
                    $gateway->setSavedCardToken($newSavedToken);
                }
                if ($paymentNonce) {
                    $gateway->setPaymentNonce($paymentNonce);
                }
                if ($paymentMethod === PaymentMethodEnum::STRIPE && $paymentNonce) {
                    $gateway->setPaymentIntent($paymentNonce);
                }

                $payment = $gateway->startPayment()->execute();
                if ($payment['success']) {
                    if ($payment['action'] === 'redirect') {
                        return $this->redirect($payment['redirectUrl']);
                    }

                    $cartManagerService->issueNewCart();
                    $this->eventDispatcher->dispatch(new OrderReceivedEvent($order, $form->get('discountForNextOrder')->getData()), OrderReceivedEvent::NAME);
                    $session->set('newOrder', true);
                    $session->set('orderId', $order->getOrderId());
                    return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
                }
                $this->addFlash('danger', $payment['message']);

            }
        } else {
            if ($form->isSubmitted() && !$form->isValid()) {
                $limiter->consume();
            }
        }

        $amazonPayData = $amazonPay->getSignature();
        $amazonCheckoutSessionData = null;
        $currentPath = $request->getPathInfo();
        if ($request->get('amazonCheckoutSessionId')) {
            $sessionId = $request->get('amazonCheckoutSessionId');
            $sessionChargeResult = $amazonPay->handleSessionAndCharge($sessionId, $cart->getTotalAmount(), "USD", '0', $currentPath);
            if ($sessionChargeResult['success']) {
                $amazonCheckoutSessionData = $sessionChargeResult['data'];
            } else {
                $this->addFlash('danger', $sessionChargeResult['message'] ?? 'Invalid Payment Details');
                return $this->redirectToRoute('cart');
            }
        }

        $amazonPayCheckoutData = [
            'signature' => $amazonPayData['signature'] ?? null,
            'payload' => $amazonPayData['payload'] ?? null,
            'returnUrl' => $amazonPayData['checkoutResultReturnUrl'] ?? null,
            'checkoutSession' => $amazonCheckoutSessionData,
        ];

        $savedAddresses = $addressService->getAllAddresses($this->getUser());
       
        $savedCards = [];
        if($this->getUser()) {
            $savedCards = $savedPaymentDetailService->getSavedPaymentDetails($this->getUser()); 
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form,
            'cart' => $cart,
            'order' => $order,
            'amazonPay' => $amazonPayCheckoutData,
            'internationalShippingCharge' => $internationalShippingCharge,
            'savedAddresses' => $savedAddresses,
            'savedCards' => $savedCards,
        ]);
    }

}