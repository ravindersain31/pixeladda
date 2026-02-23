<?php

namespace App\Payment\Stripe;

use Stripe\Price;
use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Payment\PaymentInterface;
use Stripe\StripeClient;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Stripe extends Base implements PaymentInterface
{
    private ?string $actionOnSuccess = null;
    private StripeClient $stripeClient;
    protected ?Price $price = null; // Initialize as null
    protected Order $order;
    protected bool $isCustomAmount = false;
    protected string $currency = 'USD';

    private array $shippingAddress = [];
    private array $billingAddress = [];

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly OrderLogger $orderLogger,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct($parameterBag, $urlGenerator);
        $this->secretKey = $this->parameterBag->get('STRIPE_SECRET_KEY');
        $this->stripeClient = new StripeClient($this->secretKey);
    }

    private function updateUrls(Order $order): void
    {
        $urlParams = ['orderId' => $order->getOrderId()];
        if ($this->actionOnSuccess) {
            $urlParams['actionOnSuccess'] = $this->actionOnSuccess;
        }

        $returnUrl = $this->urlGenerator->generate('stripe_return', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->urlGenerator->generate('stripe_cancel', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $this->setRedirectUrls($returnUrl, $cancelUrl);
    }

    public function setShippingAddress(array $address): void
    {
        $this->shippingAddress = $this->makeAddress($address);
    }

    public function setBillingAddress(array $address): void
    {
        $this->billingAddress = $this->makeAddress($address);
    }

    private function convertToCents(float $amount): float
    {
        return (float) ($amount * 100);
    }

    public function createCheckoutSession(Order $order, float $customAmount = 0): array
    {
        $this->updateUrls($order);

        try {

            $shippingOption = [];
            $discounts = [];

            if ($customAmount > 0) {
                $this->isCustomAmount = true;
                $lineItems = $this->createCustomAmountLineItems($order, $customAmount);
            }else{
                $lineItems = array_merge(
                    $this->createLineItems($order),
                    $this->createOptionalLineItems($order)
                );
                $shippingOption = $this->createShippingOption($order);
                $discounts = $this->createDiscounts($order);
            }

            $metaData = $this->createMetadata($order);
            $email = $order->getUser()->getEmail() ?? $order->getBillingAddress()['email'];

            $session = $this->stripeClient->checkout->sessions->create([
                'line_items' => $lineItems,
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'shipping_options' => $shippingOption,
                'discounts' => $discounts,
                'client_reference_id' => $order->getOrderId(),
                'customer_email' => $email,
                'payment_intent_data' => [
                    'shipping' => $this->shippingAddress,
                    'receipt_email' => $email,
                    'setup_future_usage' => 'on_session',
                    'metadata' => $metaData,
                    'customer' => [
                        'billingAddress' => $this->billingAddress,
                        'shippingAddress' => $this->shippingAddress
                    ],
                    'shipping_details' => $this->shippingAddress,
                ],
                'success_url' => $this->getReturnUrl(),
                'cancel_url' => $this->getCancelUrl(),
            ]);

            return [
                'success' => true,
                'transaction' => [
                    'gatewayId' => $session->id,
                ],
                'redirectUrl' => $session->url,
                'action' => 'redirect',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createPaymentIntent(float $amount = 0): array
    {
        try {

            $paymentIntent = $this->stripeClient->paymentIntents->create([
                'amount' => $this->convertToCents($amount),
                'currency' => $this->currency,
            ]);

            return [
                'success' => true,
                'response' => $paymentIntent,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

    private function createMetadata(Order $order): array
    {
        $metadata = [
            'orderId' => $order->getOrderId(),
            'email' => $order->getBillingAddress()['email'] ?? $order->getUser()->getEmail(),
            'name' => $order->getBillingAddress()['name'] ?? $order->getUser()->getName(),
            'phone' => $order->getBillingAddress()['phone'] ?? '',
        ];

        return $metadata;
    }

    private function createLineItems(Order $order): array
    {
        return array_map(fn($item) => [
            'price_data' => [
                'currency' => $this->currency,
                'product_data' => ['name' => $this->getProductName($item)],
                'unit_amount' => $this->convertToCents($item->getUnitAmount()),
            ],
            'quantity' => $item->getQuantity(),
        ], $order->getOrderItems()->toArray());
    }

    private function createOptionalLineItems(Order $order): array
    {
        $lineItems = [];

        if ($order->getOrderProtectionAmount() > 0) {
            $lineItems[] = $this->createSingleLineItem('Order Protection', 'Order protection for your order', $order->getOrderProtectionAmount());
        }

        return $lineItems;
    }

    private function createCustomAmountLineItems(Order $order, float $customAmount): array
    {
        return [
            $this->createSingleLineItem('Total Amount', 'Total Amount', $customAmount),
        ];
    }

    private function createSingleLineItem(string $name, string $description, float $amount): array
    {
        return [
            'price_data' => [
                'currency' => $this->currency,
                'product_data' => ['name' => $name, 'description' => $description],
                'unit_amount' => $this->convertToCents($amount),
            ],
            'quantity' => 1,
        ];
    }

    private function calculateTotalOrderDiscount(Order $order): float
    {
        $couponDiscount = $order->getCouponDiscountAmount();
        $additionalDiscount = $this->calculateAdditionalDiscounts($order);

        return (float) ($couponDiscount + $additionalDiscount);
    }

    private function calculateAdditionalDiscounts(Order $order): float
    {
        $discount = 0;
        $additionalDiscounts = $order->getAdditionalDiscount();

        if (is_array($additionalDiscounts)) {
            foreach ($additionalDiscounts as $discountData) {
                if (isset($discountData['amount']) && $discountData['amount'] > 0) {
                    $discount += $discountData['amount'];
                }
            }
        }

        return $discount;
    }


    private function createShippingOption(Order $order): array
    {
        if ($order->getShippingAmount() > 0) {
            return [
                [
                    'shipping_rate_data' => [
                        'type' => 'fixed_amount',
                        'fixed_amount' => [
                            'amount' => $this->convertToCents($order->getShippingAmount()),
                            'currency' => $this->currency,
                        ],
                        'display_name' => 'Standard Shipping',
                    ]
                ]
            ];
        }

        return [];
    }

    private function createDiscounts(Order $order): array
    {
        $discountAmount = $this->calculateTotalOrderDiscount($order);
        if ($discountAmount > 0) {
            $coupon = $this->stripeClient->coupons->create([
                'amount_off' => $this->convertToCents($discountAmount),
                'currency' => $this->currency,
                'duration' => 'once',
            ]);
            return [
                ['coupon' => $coupon->id],
            ];
        }

        return [];
    }

    public function createPrice(int $amountInCents): void
    {
        if ($this->price === null) {
            $this->price = $this->stripeClient->prices->create([
                'currency' => $this->currency,
                'unit_amount' => $amountInCents,
                'product_data' => ['name' => 'Yard Sign Plus'],
            ]);
        }
    }

    public function charge(Order $order, float $customAmount = 0): array
    {

        try {

            $response = $this->verifyPaymentIntent($this->paymentIntent);

            $this->updatePaymentIntent($order);

            if ($response->status === 'succeeded') {
                return [
                    'success' => true,
                    'action' => 'completed',
                    'message' => 'Payment successful',
                    'transaction' => [
                        'gatewayId' => $this->paymentIntent,
                    ],
                ];
            } elseif ($response->status === 'processing') {
                return [
                    'success' => false,
                    'action' => 'pending',
                    'message' => 'Your payment is processing.',
                    'transaction' => [
                        'gatewayId' => $this->paymentIntent,
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'action' => 'completed',
                    'message' => 'Payment failed',
                    'transaction' => [
                        'gatewayId' => $this->paymentIntent,
                    ],
                ];
            }

        }catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function updatePaymentIntent(Order $order): void
    {
        $metaData = $this->createMetadata($order);

        $metaData['orderId'] = $order->getOrderId();
        if(isset($this->customFields['transaction_id'])){
            $transaction = $this->entityManager->getRepository(OrderTransaction::class)->findOneBy([
                'id' => $this->customFields['transaction_id'],
            ]);

            if($transaction instanceof OrderTransaction){
                $metaData['transactionId'] = $transaction->getTransactionId();
            }

        }

        $this->stripeClient->paymentIntents->update($this->paymentIntent, [
            'metadata' => $metaData,
        ]);
    }

    public function verifyPaymentIntent(string $intentId): \Stripe\PaymentIntent
    {
        return $this->stripeClient->paymentIntents->retrieve($intentId, []);
    }

    public function chargeSession(Order $order, float $customAmount = 0, ?string $paymentMethodId = null): array
    {
        try {
            // $amount = $this->convertToCents($customAmount > 0 ? $customAmount : $order->getTotalAmount());
            // $this->createPrice($amount);

            if ($paymentMethodId) {
                return [
                    'success' => false,
                    'message' => 'Payment method not supported',
                ];
            } else {
                $session = $this->createCheckoutSession($order, $customAmount);
                $this->handleCheckoutSessionResponse($order, $session);
                return $session;
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getProductName($item): string
    {
        $template = $item->getProduct();
        $product = $template->getParent();
        if ($item->getMetaDataKey('isCustomSize')) {
            $name = 'CUSTOM-SIZE - ' . ($item->getMetaDataKey('customSize')['templateSize']['width'] . 'x' . $item->getMetaDataKey('customSize')['templateSize']['height']);
        } else {
            $name = ''.$product->getSku().' - '.$template->getName();
        }
        return $name;
    }

    private function handleCheckoutSessionResponse(Order $order, array $session): void
    {
        $gatewayId = $session['transaction']['gatewayId'] ?? '';
        $redirectUrl = $session['redirectUrl'] ?? '';

        $redirectLink = $redirectUrl ? sprintf('<a href="%s">Redirect Link: </a>', $redirectUrl) : 'No redirect URL';

        $this->orderLogger->setOrder($order);
        $this->orderLogger->log(
            sprintf(
                'Stripe payment processed for Order ID %s - Gateway ID: %s - %s',
                $order->getOrderId(),
                $gatewayId,
                $redirectLink
            )
        );

        if ($order->getTransactions()->count() > 0) {
            $transaction = $order->getTransactions()->last();
            $transaction->setMetadataKey('redirectUrl', $redirectUrl);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
        }
    }



    public function makeAddress(array $address): array
    {
        return [
            'name' => $address['firstName'] . ' ' . $address['lastName'],
            'line1' => $address['addressLine1'],
            'line2' => $address['addressLine2'] ?? '',
            'city' => $address['city'],
            'state' => $address['state'],
            'postal_code' => $address['zipcode'],
            'country' => $address['country'],
        ];
    }

    private function getNameFromAddress(array $address): string
    {
        return $address['firstName'] . ' ' . $address['lastName'];
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }
}
