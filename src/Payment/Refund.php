<?php

namespace App\Payment;

use App\Entity\OrderTransaction;
use App\Entity\OrderTransactionRefund;
use App\Entity\User;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Payment\Affirm\AffirmRefund;
use App\Payment\AmazonPay\AmazonPayRefund;
use App\Payment\Braintree\ApplePayRefund;
use App\Payment\Braintree\BraintreeRefund;
use App\Payment\Braintree\GooglePayRefund;
use App\Payment\Paypal\PaypalRefund;
use App\Payment\Stripe\StripeRefund;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Refund
{

    private PaypalRefund|BraintreeRefund|GooglePayRefund|StripeRefund|AmazonPayRefund|AffirmRefund|ApplePayRefund $refundProcessor;

    private UserInterface|User $refundedBy;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaypalRefund           $paypalRefund,
        private readonly BraintreeRefund        $braintreeRefund,
        private readonly GooglePayRefund        $googlePayRefund,
        private readonly StripeRefund           $stripe,
        private readonly AmazonPayRefund        $amazonPayRefund,
        private readonly AffirmRefund           $affirmRefund,
        private readonly ApplePayRefund         $applePayRefund,
        private readonly OrderService           $orderService
    )
    {
    }

    public function setRefundedBy(UserInterface|User $refundedBy): void
    {
        $this->refundedBy = $refundedBy;
    }


    public function refund(OrderTransaction $transaction, array $data): array
    {
        $this->initialize($transaction->getPaymentMethod());
        $amount = $data['amount'] ?? 0;
        $response = $this->refundProcessor->refund($transaction, $amount);
        if (isset($response['success']) && $response['success']) {
            $refund = $this->handleSuccess($response, $transaction, $data);
            return [
                ...$response,
                'refund' => $refund,
            ];
        }
        return $response;
    }

    private function handleSuccess(array $response, OrderTransaction $transaction, array $data): OrderTransactionRefund
    {
        $order = $transaction->getOrder();

        $refund = new OrderTransactionRefund();

        if (isset($response['metaData'])) {
            foreach ($response['metaData'] as $metaKey => $metaData) {
                $refund->setMetaDataKey($metaKey, $metaData);
                $transaction->setMetaDataKey($metaKey, $metaData);
            }
        }

        $refund->setRefundType($data['refundType']);
        $refund->setMetaDataKey('internalNote', $data['internalNote']);
        $refund->setMetaDataKey('customerNote', $data['customerNote']);
        $refund->setAmount($response['totalRefundedAmount']);
        $refund->setTransaction($transaction);
        $refund->setStatus('COMPLETED');
        $refund->setRefundedAt(new \DateTimeImmutable());
        $refund->setRefundedBy($this->refundedBy);

        $transaction->setRefundedAmount($transaction->getRefundedAmount() + $response['totalRefundedAmount']);
        if ($transaction->getRefundedAmount() >= $transaction->getAmount()) {
            $transaction->setStatus(PaymentStatusEnum::REFUNDED);
        } else {
            $transaction->setStatus(PaymentStatusEnum::PARTIALLY_REFUNDED);
        }

        if (!$transaction->isIsPaymentLink()) {
            $order->setRefundedAmount($order->getRefundedAmount() + $response['totalRefundedAmount']);
            if ($order->getRefundedAmount() >= $order->getTotalAmount()) {
                $order->setPaymentStatus(PaymentStatusEnum::REFUNDED);
            } else {
                $order->setPaymentStatus(PaymentStatusEnum::PARTIALLY_REFUNDED);
            }
        }

        $this->entityManager->persist($refund);
        $this->entityManager->persist($transaction);
        $this->orderService->updatePaymentStatus($order);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $refund;
    }

    private function initialize(string $paymentMethod): void
    {
        $this->refundProcessor = match ($paymentMethod) {
            PaymentMethodEnum::CREDIT_CARD => $this->braintreeRefund,
            PaymentMethodEnum::STRIPE => $this->stripe,
            PaymentMethodEnum::GOOGLE_PAY => $this->googlePayRefund,
            PaymentMethodEnum::PAYPAL,
            PaymentMethodEnum::PAYPAL_EXPRESS => $this->paypalRefund,
            PaymentMethodEnum::AMAZON_PAY => $this->amazonPayRefund,
            PaymentMethodEnum::AFFIRM => $this->affirmRefund,
            PaymentMethodEnum::APPLE_PAY => $this->applePayRefund,
            default => null,
        };

        if (!$this->refundProcessor) {
            $this->throwException('Payment method not supported');
        }
    }

    private function throwException(string $message): void
    {
        throw new \Exception($message);
    }

}