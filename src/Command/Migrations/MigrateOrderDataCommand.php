<?php

namespace App\Command\Migrations;

use App\Entity\AdminUser;
use App\Entity\Currency;
use App\Entity\Order;
use App\Entity\OrderLog;
use App\Entity\OrderTransaction;
use App\Entity\OrderTransactionRefund;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Service\CartPriceManagerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:order:data',
    description: 'Add a short description for your command',
)]
class MigrateOrderDataCommand extends Command
{

    private array $paymentMethodConfig = [
        'braintree' => PaymentMethodEnum::CREDIT_CARD,
        'creditcard' => PaymentMethodEnum::CREDIT_CARD,
        'paypal' => PaymentMethodEnum::PAYPAL,
        'paypal_express_checkout' => PaymentMethodEnum::PAYPAL_EXPRESS,
        'paylater' => PaymentMethodEnum::SEE_DESIGN_PAY_LATER,
        'cheque' => PaymentMethodEnum::CHECK,
        'googlepay' => PaymentMethodEnum::GOOGLE_PAY,
    ];

    private array $paymentStatusConfig = [
        'COMPLETED' => PaymentStatusEnum::COMPLETED,
        'submitted_for_settlement' => PaymentStatusEnum::COMPLETED,
        'Completed' => PaymentStatusEnum::COMPLETED,
        '' => PaymentStatusEnum::PROCESSING,
        'succeeded' => PaymentStatusEnum::COMPLETED,
        'Instant' => PaymentStatusEnum::COMPLETED,
        'Delayed' => PaymentStatusEnum::COMPLETED,
    ];

    private ?Currency $currency;


    public function __construct(
        private readonly EntityManagerInterface  $entityManager,
        private readonly CartPriceManagerService $cartPriceManagerService,
    )
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
        $this->currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orders = $this->entityManager->getRepository(Order::class)->findBy(['version' => 'V1'], []);

        $index = 1;
        /** @var Order $order */
        foreach ($orders as $order) {
            $io->comment('#' . $index . ' Working for Order Id: ' . $order->getOrderId());
            $cart = $this->cartPriceManagerService->recalculateCartPrice($order->getCart());
            $order->setSubTotalAmount($cart->getSubTotal());
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $migratedData = $order->getMetaDataKey('migratedData');
            $data = $this->getData(intval($migratedData['order_id']));
            $this->saveLogs($order, $data['logs']);
            $this->saveTransaction($order, $data['transactions']);
            $this->saveRefunds($data['refunds']);
            $index++;
        }
        $io->success('Migration Completed');

        return Command::SUCCESS;
    }

    private function saveLogs(Order $order, $logs): void
    {
        foreach ($logs as $log) {
            $exists = $this->entityManager->getRepository(OrderLog::class)->findOneBy(['content' => $log['comment']]);
            if ($exists instanceof OrderLog) {
                continue;
            }
            $note = new OrderLog();
            $note->setOrder($order);
            $note->setChangedBy($this->entityManager->getReference(AdminUser::class, 4));
            $note->setContent($log['comment']);
            $note->setType(OrderLog::ORDER_UPDATED);
            $note->setCreatedAt(new \DateTimeImmutable($log['created_at']));
            $this->entityManager->persist($note);
            $this->entityManager->flush();
        }
    }

    private function saveTransaction(Order $order, $transactions): void
    {
        foreach ($transactions as $transaction) {
            $exists = $this->entityManager->getRepository(OrderTransaction::class)->findOneBy(['transactionId' => $transaction['id']]);
            if ($exists instanceof OrderTransaction) {
                continue;
            }

            $isPaymentLink = str_contains($transaction['description'], 'Quick Link');
            $orderTransaction = new OrderTransaction();
            $orderTransaction->setOrder($order);
            $orderTransaction->setTransactionId($transaction['id']);
            $orderTransaction->setIsPaymentLink($isPaymentLink);
            $orderTransaction->setCurrency($this->currency);
            $orderTransaction->setAmount($transaction['amount']);
            $orderTransaction->setRefundedAmount($transaction['refunded_amount']);
            $orderTransaction->setComment($transaction['description']);
            $orderTransaction->setStatus($this->paymentStatusConfig[$transaction['status']]);
            $orderTransaction->setPaymentMethod($this->paymentMethodConfig[$transaction['payment_library']]);
            $orderTransaction->setGatewayId($transaction['transaction_id']);
            $orderTransaction->setMetaDataKey('migratedData', $transaction);
            $orderTransaction->setUpdatedAt(new \DateTimeImmutable($transaction['updated_at']));
            $orderTransaction->setCreatedAt(new \DateTimeImmutable($transaction['created_at']));

            if ($isPaymentLink) {
                $paymentLink = $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_payment_quick_links AS O WHERE O.amount='" . $transaction['amount'] . "' AND O.order_id = " . $order->getOrderId());
                if ($paymentLink) {
                    $orderTransaction->setMetaDataKey('internalNote', $paymentLink['internal_note']);
                    $orderTransaction->setMetaDataKey('customerNote', $paymentLink['customer_note']);
                    $orderTransaction->setCreatedAt(new \DateTimeImmutable($paymentLink['created_at']));
                }
            }
            $this->entityManager->persist($orderTransaction);
            $this->entityManager->flush();
        }
    }

    private function saveRefunds($refunds): void
    {
        foreach ($refunds as $refund) {
            $transaction = $this->entityManager->getRepository(OrderTransaction::class)->findOneBy(['transactionId' => $refund['ref_id']]);
            if ($transaction instanceof OrderTransaction) {
                $refundTransaction = new OrderTransactionRefund();
                $refundTransaction->setTransaction($transaction);
                $refundTransaction->setRefundedBy($this->entityManager->getReference(AdminUser::class, 4));
                $refundTransaction->setStatus($this->paymentStatusConfig[$refund['status']]);
                $refundTransaction->setAmount($refund['amount']);
                $refundTransaction->setRefundType('PARTIAL_REFUND');
                $refundTransaction->setMetaDataKey('migratedData', $refund);
                $refundTransaction->setRefundedAt(new \DateTimeImmutable($refund['updated_at']));
                $refundTransaction->setCreatedAt(new \DateTimeImmutable($refund['created_at']));
                $this->entityManager->persist($refundTransaction);
                $this->entityManager->flush();
            }
        }
    }

    private function getData(int $orderId): array
    {
        return [
            'transactions' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_payment_transaction_log AS O WHERE O.type=1 AND O.order_id = " . $orderId),
            'refunds' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_payment_transaction_log AS O WHERE O.type=2 AND O.order_id = " . $orderId),
            'logs' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_history AS O WHERE O.order_id = " . $orderId),
        ];
    }

}
