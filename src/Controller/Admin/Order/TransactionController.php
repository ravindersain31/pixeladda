<?php

namespace App\Controller\Admin\Order;

use App\Entity\AppUser;
use App\Entity\Currency;
use App\Entity\Order;
use App\Entity\OrderLog;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\StoreConfigEnum;
use App\Event\OrderReceivedEmailEvent;
use App\Form\Admin\Order\ChangeOrderStatusType;
use App\Form\Admin\Order\ChargeCardType;
use App\Form\Admin\Order\FilterOrderType;
use App\Form\Admin\Order\RepeatOrderFilterType;
use App\Form\Admin\Order\UpdateBillingAddressType;
use App\Form\Admin\Order\UpdateShippingAddressType;
use App\Form\Admin\Order\UploadPrintFileType;
use App\Payment\Gateway;
use App\Repository\AppUserRepository;
use App\Repository\OrderLogRepository;
use App\Repository\OrderRepository;
use App\Repository\OrderTransactionRepository;
use App\Service\CartManagerService;
use App\Service\ExportService;
use App\Service\OrderLogger;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TransactionController extends AbstractController
{
    #[Route('/orders/{orderId}/transactions', name: 'order_transactions')]
    public function transactions(string $orderId, Request $request, OrderRepository $repository, EntityManagerInterface $entityManager, OrderLogger $orderLogger, Gateway $gateway): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $chargeCardForm = $this->createForm(ChargeCardType::class);
        $chargeCardForm->handleRequest($request);
        if ($chargeCardForm->isSubmitted() && $chargeCardForm->isValid()) {
            $data = $chargeCardForm->getData();

            $currency = $entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);

            $chargeCard = new OrderTransaction();
            $chargeCard->setOrder($order);
            $chargeCard->setAmount($data['amount']);
            $chargeCard->setCurrency($currency);
            $chargeCard->setStatus(PaymentStatusEnum::INITIATED);
            $chargeCard->setPaymentMethod(PaymentMethodEnum::CREDIT_CARD);
            $chargeCard->setMetaDataKey('chargeCard', true);
            $chargeCard->setMetaDataKey('internalNote', $data['internalNote']);
            $chargeCard->setMetaDataKey('customerNote', $data['customerNote']);

            $entityManager->persist($chargeCard);
            $entityManager->flush();

            $gateway->initialize($chargeCard->getPaymentMethod(), $currency->getCode());
            $gateway->setOrder($order);
            $gateway->setTransaction($chargeCard);
            $gateway->setCustomAmount($data['amount']);
            $gateway->setPaymentNonce($data['paymentNonce']);
            $payment = $gateway->startPayment()->execute();
            if ($payment['success']) {
                if ($payment['action'] === 'redirect') {
                    $this->addFlash('warning', $payment['message']);
                    return $this->redirect($payment['redirectUrl']);
                }
                $order->setChargeCardAmount($data['amount']);
                $repository->save($order, true);
                $this->addFlash('success', 'Payment has been charged successfully');
            } else {
                $this->addFlash('danger', $payment['message']);
            }
            return $this->redirectToRoute('admin_order_transactions', ['orderId' => $order->getOrderId()]);

        }

        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
            'chargeCardForm' => $chargeCardForm->createView(),
        ]);
    }

    #[Route('/orders/transaction/cancel/{transactionId}', name: 'order_transaction_cancel')]
    public function transactionCancel(string $transactionId, Request $request, OrderTransactionRepository $repository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $transaction = $repository->findOneBy(['transactionId' => $transactionId]);
        if (!$transaction instanceof OrderTransaction) {
            throw $this->createNotFoundException('Transaction not found');
        }

        $transaction->setStatus(PaymentStatusEnum::CANCELLED);
        $transaction->setUpdatedAt(new \DateTimeImmutable());
        $repository->save($transaction, true);

        $this->addFlash('success', 'Transaction has been cancelled successfully');
        return $this->redirectToRoute('admin_order_transactions', ['orderId' => $transaction->getOrder()->getOrderId()]);
    }

}
