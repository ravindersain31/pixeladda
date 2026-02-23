<?php

namespace App\Payment;

use App\Entity\Cart;
use App\Entity\Currency;
use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderProofApprovedEvent;
use App\Payment\Affirm\Affirm;
use App\Payment\AmazonPay\AmazonPay;
use App\Payment\Braintree\ApplePay;
use App\Payment\Braintree\Braintree;
use App\Payment\Braintree\GooglePay;
use App\Payment\Paypal\Paypal;
use App\Payment\Stripe\Stripe;
use App\Payment\Paypal\PaypalExpress;
use App\Payment\SeeDesignPayLater\SeeDesignPayLater;
use App\Payment\CheckPO\CheckPO;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Gateway
{

    private Order $order;

    private ?array $store;

    private string $paymentMethod;

    private ?string $paymentNonce = null;

    private ?string $savedCardToken = null;

    private ?string $paymentIntent = null;

    private float $customAmount = 0;

    /** @var string|null $actionOnSuccess Expected Values [APPROVE_PROOF] */
    private ?string $actionOnSuccess = null;

    private Currency $currency;

    private array $paymentData = [];

    private ?OrderTransaction $transaction = null;

    private Braintree|GooglePay|Paypal|Stripe|SeeDesignPayLater|CheckPO|PaypalExpress|AmazonPay|Affirm|ApplePay|null $paymentProcessor;

    public function __construct(
        private readonly EntityManagerInterface       $entityManager,
        private readonly Braintree                    $braintree,
        private readonly GooglePay                    $googlePay,
        private readonly Paypal                       $paypal,
        private readonly PaypalExpress                $paypalExpress,
        private readonly SeeDesignPayLater            $seeDesignPayLater,
        private readonly CheckPO                      $checkPO,
        private readonly Stripe                       $stripe,
        private readonly OrderLogger                  $orderLogger,
        private readonly EventDispatcherInterface     $eventDispatcher,
        private readonly AmazonPay                    $amazonPay,
        private readonly Affirm                       $affirm,
        private readonly ApplePay                     $applePay,
    ) {}

    public function initialize(string $paymentMethod, Currency|string $currency): void
    {
        if (!$currency instanceof Currency) {
            $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => $currency]);
        }
        $this->currency = $currency;

        $this->paymentMethod = $paymentMethod;
        $this->paymentProcessor = match ($paymentMethod) {
            PaymentMethodEnum::CREDIT_CARD => $this->braintree,
            PaymentMethodEnum::GOOGLE_PAY => $this->googlePay,
            PaymentMethodEnum::PAYPAL => $this->paypal,
            PaymentMethodEnum::PAYPAL_EXPRESS => $this->paypalExpress,
            PaymentMethodEnum::SEE_DESIGN_PAY_LATER => $this->seeDesignPayLater,
            PaymentMethodEnum::CHECK => $this->checkPO,
            PaymentMethodEnum::STRIPE => $this->stripe,
            PaymentMethodEnum::AMAZON_PAY => $this->amazonPay,
            PaymentMethodEnum::AFFIRM => $this->affirm,
            PaymentMethodEnum::APPLE_PAY => $this->applePay,
            default => null,
        };

        if (!$this->paymentProcessor) {
            $this->throwException('Payment method not supported');
        }
    }

    public function execute(): array
    {
        $this->paymentProcessor->setCustomFields([
            'order_id' => $this->order->getOrderId(),
            'transaction_id' => $this->transaction->getId(),
        ]);
        $this->paymentProcessor->setCurrencyCode($this->currency->getCode());

        $this->paymentProcessor->setPaymentNonce($this->paymentNonce);
        $this->paymentProcessor->setPaymentIntent($this->paymentIntent);

        if ($this->actionOnSuccess) {
            $this->paymentProcessor->setActionOnSuccess($this->actionOnSuccess);
        }
        if ($this->paymentData) {
            $this->paymentProcessor->setPaymentData($this->paymentData);
        }

        if ($this->paymentMethod === PaymentMethodEnum::CREDIT_CARD && $this->savedCardToken) {
            $payment = $this->paymentProcessor->chargeWithToken($this->order, $this->savedCardToken, $this->customAmount);
        } else {
            $payment = $this->paymentProcessor->charge($this->order, $this->customAmount);
        }


        $this->updateTransaction($payment);

        return $payment;
    }

    public function startPayment(): static
    {

        $transaction = $this->transaction;
        if (!$transaction instanceof OrderTransaction) {
            $transaction = new OrderTransaction();
        }

        $transaction->setPaymentMethod($this->paymentMethod);
        $transaction->setCurrency($this->currency);
        $transaction->setOrder($this->order);
        if ($this->customAmount > 0) {
            $transaction->setAmount($this->customAmount);
        } else {
            $transaction->setAmount($this->order->getTotalAmount());
        }
        $transaction->setStatus(PaymentStatusEnum::INITIATED);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->transaction = $transaction;

        $this->orderLogger->setOrder($this->order);
        $this->orderLogger->log('Processing the payment of Transaction Id ' . $transaction->getTransactionId() . ' with payment method ' . $this->paymentMethod);

        return $this;
    }

    public function updateTransaction(array $payment): void
    {
        $this->transaction->setComment($payment['message'] ?? null);
        if ($payment['success']) {
            if (isset($payment['transaction'])) {
                $this->transaction->setGatewayId($payment['transaction']['gatewayId']);
            }
            if ($payment['action'] === 'completed') {
                $this->transaction->setStatus(PaymentStatusEnum::COMPLETED);
                if ($this->transaction->isIsPaymentLink()) {
                    $this->order->setPaymentLinkAmountReceived($this->order->getPaymentLinkAmountReceived() + $this->transaction->getAmount());
                    $this->order->setTotalReceivedAmount(floatval($this->order->getTotalReceivedAmount()) + floatval($this->transaction->getAmount()));
                } else {
                    $orderStatus = $this->determineOrderStatus();
                    $this->order->setStatus($orderStatus);
                    $this->order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
                    $this->order->setTotalReceivedAmount(floatval($this->order->getTotalReceivedAmount()) + floatval($this->transaction->getAmount()));
                    if (strtoupper($this->actionOnSuccess ?? '') === 'APPROVE_PROOF') {
                        $approvedProof = $this->entityManager->getRepository(OrderMessage::class)->getLastProofMessage($this->order);
                        $this->eventDispatcher->dispatch(new OrderProofApprovedEvent($this->order, $approvedProof), OrderProofApprovedEvent::NAME);
                        $this->order->setApprovedProof($approvedProof);
                        $this->order->setProofApprovedAt(new \DateTimeImmutable());
                        $this->order->setIsApproved(true);
                        $this->order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                    }
                }
            } elseif ($payment['action'] === 'pending') {
                $status = $this->order->getTotalAmount() == 0
                    ? PaymentStatusEnum::COMPLETED
                    : PaymentStatusEnum::PENDING;
                $orderStatus = $this->determineOrderStatus();
                $this->order->setStatus($orderStatus);
                $this->order->setPaymentStatus($status);
                $this->transaction->setStatus(PaymentStatusEnum::VOIDED);
            } elseif ($payment['action'] === 'redirect') {
                $this->transaction->setStatus(PaymentStatusEnum::REDIRECTED_TO_GATEWAY);
            } else {
                $this->transaction->setStatus(PaymentStatusEnum::UNKNOWN);
            }
        } else {
            $this->transaction->setStatus(PaymentStatusEnum::FAILED);
        }
        $this->entityManager->persist($this->order);
        $this->entityManager->persist($this->transaction);
        $this->entityManager->flush();

        $this->orderLogger->setOrder($this->order);
        $this->orderLogger->log('Transaction Id ' . $this->transaction->getTransactionId() . ' status has been updated to ' . $this->transaction->getStatus());
    }

    public function getPaymentProcessor(): Braintree|Stripe|Paypal|SeeDesignPayLater|CheckPO|AmazonPay|Affirm|ApplePay|null
    {
        return $this->paymentProcessor;
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function setTransaction(OrderTransaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function setStore(?array $store): void
    {
        $this->store = $store;
    }

    public function setCustomAmount(float $customAmount): void
    {
        $this->customAmount = $customAmount;
    }

    public function setActionOnSuccess(?string $actionOnSuccess): void
    {
        $this->actionOnSuccess = $actionOnSuccess;
    }


    public function setPaymentNonce(string $paymentNonce): void
    {
        $this->paymentNonce = $paymentNonce;
    }

    public function setSavedCardToken(string $savedCardToken): void
    {
        $this->savedCardToken = $savedCardToken;
    }

    public function setPaymentIntent(string $paymentIntent): void
    {
        $this->paymentIntent = $paymentIntent;
    }

    public function setPaymentData(array $paymentData): void
    {
        $this->paymentData = $paymentData;
    }


    private function throwException(string $message): void
    {
        throw new \Exception($message);
    }

    private function determineOrderStatus(): string
    {
        $cart = $this->order->getCart();
        $orderEligibleForAutoApproval = true;

        if ($cart instanceof Cart) {
            foreach ($cart->getCartItems() as $item) {
                $data = $item->getData();
                $isWireStake = isset($data['isWireStake']) && $data['isWireStake'] === true;
                $isBlankSign = isset($data['isBlankSign']) && $data['isBlankSign'] === true;

                if (!$isWireStake && !$isBlankSign) {
                    $orderEligibleForAutoApproval = false;
                    break;
                }
            }
        } else {
            $orderEligibleForAutoApproval = false;
        }

        if ($orderEligibleForAutoApproval || !$this->order->isNeedProof()) {
            return OrderStatusEnum::PROOF_APPROVED;
        }

        return OrderStatusEnum::RECEIVED;
    }

}
