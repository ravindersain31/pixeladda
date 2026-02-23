<?php

namespace App\Controller\Web\MyAccount;

use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\UserFile;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Event\OrderChangesRequestedEvent;
use App\Event\OrderProofApprovedEvent;
use App\Form\ApproveProofType;
use App\Form\RequestChangesType;
use App\Form\UpdateAddressType;
use App\Helper\AddressHelper;
use App\Payment\Gateway;
use App\Service\CogsHandlerService;
use App\Service\OrderDeliveryDateService;
use App\Service\OrderLogger;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Helper\VichS3Helper;
use App\Payment\AmazonPay\AmazonPay;
use App\Service\SavedPaymentDetailService;
use App\Service\StoreInfoService;
use App\Service\SlackManager;
use App\SlackSchema\AddressUpdatedSchema;
use App\SlackSchema\OrderApprovedSchema;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProofController extends AbstractController
{
    use StoreTrait;

    #[Route(path: '/order/{oid}/proofs', name: 'order_proof_redirect')]
    public function proofsRedirect(string $oid): Response
    {
        return $this->redirectToRoute('order_proof', ['oid' => $oid]);
    }

    #[Route(path: '/order/proof/{oid}', name: 'order_proof')]
    public function proofs(string $oid, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, OrderDeliveryDateService $deliveryDateService, OrderLogger $orderLogger, VichS3Helper $s3Helper, SlackManager $slack): Response
    {

        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $oid]);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $updateAddressForm = $this->createForm(UpdateAddressType::class, $order);
        $oldShipping = $order->getShippingAddress();
        $oldBilling  = $order->getBillingAddress();

        $updateAddressForm->handleRequest($request);

        if ($updateAddressForm->isSubmitted() && $updateAddressForm->isValid()) {
            $newShipping = $order->getShippingAddress();
            $newBilling  = $order->getBillingAddress();

            $entityManager->persist($order);
            $entityManager->flush();

            if (AddressHelper::isAddressUpdated($oldShipping, $newShipping)) {
                $slack->send(
                    SlackManager::ADDRESS_CHANGE,
                    AddressUpdatedSchema::get($order, 'shippingAddress', $oldShipping, $newShipping)
                );
            }

            if (AddressHelper::isAddressUpdated($oldBilling, $newBilling)) {
                $slack->send(
                    SlackManager::ADDRESS_CHANGE,
                    AddressUpdatedSchema::get($order, 'billingAddress', $oldBilling, $newBilling)
                );
            }

            $this->addFlash('success', 'Your address has been updated successfully.');
            return $this->redirectToRoute('order_proof', ['oid' => $order->getOrderId()]);
        }

        $user = $this->getUser() ? $this->getUser() : $order->getUser();

        $messages = $entityManager->getRepository(OrderMessage::class)->getProofMessages($order);

        $form = $this->createForm(RequestChangesType::class, [
            'showPaymentOption' => false,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('files')->getData();
            $message = new OrderMessage();
            foreach ($files as $file) {
                $uploadedFile = new UserFile();
                $uploadedFile->setUploadedBy($user);
                $uploadedFile->setType('CHANGES_REQUESTED');
                $uploadedFile->setFileObject($file);
                $entityManager->persist($uploadedFile);

                $message->addFile($uploadedFile);
            }

            $message->setOrder($order);
            $message->setType('CHANGES_REQUESTED');
            $message->setContent($form->get('changes')->getData());
            $message->setSentBy($user);
            $entityManager->persist($message);

            $content = 'Customer request changes
            <br/>
            <b>Comments:</b> ' . $form->get('changes')->getData() . '
            <br/>';

            foreach ($message->getFiles() as $uploadedFile) {
                $fileUrl = $s3Helper->asset($uploadedFile, 'fileObject');
                $content .= '<a href="' . $fileUrl . '" class="btn btn-link p-0" target="_blank">View Uploaded File</a><br/>';
            }
            $orderLogger->setOrder($order);
            $orderLogger->log($content);

            $order->setStatus(OrderStatusEnum::CHANGES_REQUESTED);
            $entityManager->persist($order);
            $entityManager->flush();

            $this->eventDispatcher->dispatch(new OrderChangesRequestedEvent($order, $message), OrderChangesRequestedEvent::NAME);

            // $this->addFlash('success', 'Your changes has been requested successfully.');
            return $this->redirectToRoute('order_proof', ['oid' => $order->getOrderId()]);
        }

        return $this->render('account/order/proofs.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
            'updateAddressForm' => $updateAddressForm->createView(),
            'data' => [
                'showPaymentOption' => false,
            ],
            'messages' => $messages,
        ]);
    }

    #[Route(path: '/order/{oid}/proofs/approve', name: 'order_proof_approve_redirect')]
    public function approveProofRedirect(string $oid): Response
    {
        return $this->redirectToRoute('order_proof_approve', ['oid' => $oid]);
    }

    #[Route(path: '/order/proof/{oid}/approve', name: 'order_proof_approve')]
    public function approveProof(string $oid, Request $request, EntityManagerInterface $entityManager, Gateway $gateway, SlackManager $slack, OrderLogger $orderLogger, VichS3Helper $s3Helper, CogsHandlerService $cogs, AmazonPay $amazonPay, UrlGeneratorInterface $urlGenerator, StoreInfoService $storeInfoService, SavedPaymentDetailService $savedPaymentDetailService): Response
    {

        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $oid]);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        if (in_array($order->getStatus(), [OrderStatusEnum::CHANGES_REQUESTED])) {
            $this->addFlash('warning', 'You have requested changes on this order. You can approve the order after the new proof is available.');
            return $this->redirectToRoute('order_proof', ['oid' => $order->getOrderId()]);
        }

        $approvedProof = $entityManager->getRepository(OrderMessage::class)->getLastProofMessage($order);

        $showPaymentOption = !in_array($order->getPaymentStatus(), [PaymentStatusEnum::COMPLETED]);

        $totalAmount = round($order->getTotalAmount() + $order->getRefundedAmount() - $order->getTotalReceivedAmount(), 2);

        $form = $this->createForm(ApproveProofType::class, [
            'showPaymentOption' => $showPaymentOption,
            'totalAmount' => $totalAmount,
            'isLoggedIn' => $this->getUser() !== null,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $storeName = $storeInfoService->storeInfo($order)['storeName'];
            $thankYouMessage = 'Thank you for approving your proof and completing payment. We will begin processing your order immediately. <br/>Thank you for choosing ' . $storeName . '.';

            if ($order->getPaymentStatus() == PaymentStatusEnum::COMPLETED) {
                $this->eventDispatcher->dispatch(new OrderProofApprovedEvent($order, $approvedProof), OrderProofApprovedEvent::NAME);
                $order->setApprovedProof($approvedProof);
                $order->setProofApprovedAt(new \DateTimeImmutable());
                $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                $this->proofApprovedLog($order, $approvedProof, $orderLogger, $s3Helper);
                $order->setIsApproved(true);

                $entityManager->persist($order);
                $entityManager->flush();

                $slack->send(SlackManager::ORDER_APPROVED, OrderApprovedSchema::get($order, $urlGenerator));

                $this->addFlash('success', $thankYouMessage);
                return $this->redirectToRoute('order_proof', ['oid' => $order->getOrderId()]);
            }

            $paymentMethod = $form->get('paymentMethod')->getData();
            $paymentNonce = $form->get('paymentNonce')->getData();
            $order->setPaymentMethod($paymentMethod);

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

                $gateway->initialize($paymentMethod, 'USD');
                $gateway->setOrder($order);
                $gateway->setStore($this->store);
                $gateway->setActionOnSuccess('APPROVE_PROOF');

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

                if ($order->getTotalReceivedAmount() >= 0) {
                    $amountToBeCharged = floatval(number_format(($order->getTotalAmount() + $order->getRefundedAmount()) - $order->getTotalReceivedAmount(), 2, '.', ''));
                    $gateway->setCustomAmount($amountToBeCharged);
                }

                $payment = $gateway->startPayment()->execute();
                if ($payment['success']) {
                    if ($payment['action'] === 'redirect') {
                        return $this->redirect($payment['redirectUrl']);
                    }

                    $this->eventDispatcher->dispatch(new OrderProofApprovedEvent($order, $approvedProof), OrderProofApprovedEvent::NAME);
                    $order->setApprovedProof($approvedProof);
                    $order->setProofApprovedAt(new \DateTimeImmutable());
                    $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                    $this->proofApprovedLog($order, $approvedProof, $orderLogger, $s3Helper);
                    $order->setIsApproved(true);

                    $entityManager->persist($order);
                    $entityManager->flush();

                    $slack->send(SlackManager::ORDER_APPROVED, OrderApprovedSchema::get($order, $urlGenerator));
                    
                    $this->addFlash('success', $thankYouMessage);
                    $cogs->syncOrderSales($order->getStore(), $order->getOrderAt());
                    return $this->redirectToRoute('order_view', ['oid' => $order->getOrderId()]);
                }
                $this->addFlash('danger', $payment['message']);
            }
        }

        $updateAddressForm = $this->createForm(UpdateAddressType::class, $order);
        $oldShipping = $order->getShippingAddress();
        $oldBilling  = $order->getBillingAddress();

        $updateAddressForm->handleRequest($request);

        if ($updateAddressForm->isSubmitted() && $updateAddressForm->isValid()) {
            $newShipping = $order->getShippingAddress();
            $newBilling  = $order->getBillingAddress();

            $entityManager->persist($order);
            $entityManager->flush();

            if (AddressHelper::isAddressUpdated($oldShipping, $newShipping)) {
                $slack->send(
                    SlackManager::ADDRESS_CHANGE,
                    AddressUpdatedSchema::get($order, 'shippingAddress', $oldShipping, $newShipping)
                );
            }

            if (AddressHelper::isAddressUpdated($oldBilling, $newBilling)) {
                $slack->send(
                    SlackManager::ADDRESS_CHANGE,
                    AddressUpdatedSchema::get($order, 'billingAddress', $oldBilling, $newBilling)
                );
            }

            $this->addFlash('success', 'Your address has been updated successfully.');
            return $this->redirectToRoute('order_proof_approve', ['oid' => $order->getOrderId()]);
        }
        $orderAmount = $order->getTotalAmount() - $order->getTotalReceivedAmount();

        $amazonPayData = $amazonPay->getSignature();
        $amazonCheckoutSessionData = null;

        if ($request->get('amazonCheckoutSessionId')) {
            $sessionId = $request->get('amazonCheckoutSessionId');
            $sessionChargeResult = $amazonPay->handleSessionAndCharge($sessionId, $orderAmount, 'USD', $order->getOrderId(), "order_proof_approve");
            if ($sessionChargeResult['success']) {
                $amazonCheckoutSessionData = $sessionChargeResult['data'];
            } else {
                $this->addFlash('danger', $sessionChargeResult['message'] ?? 'Invalid Payment Details');
                return $this->redirectToRoute('order_proof_approve', ['oid' => $order->getOrderId()]);
            }
        }

        $amazonPayCheckoutData = [
            'signature' => $amazonPayData['signature'] ?? null,
            'payload' => $amazonPayData['payload'] ?? null,
            'returnUrl' => $amazonPayData['checkoutResultReturnUrl'] ?? null,
            'checkoutSession' => $amazonCheckoutSessionData,
        ];

        $savedCards = [];
        if($this->getUser()) {
            $savedCards = $savedPaymentDetailService->getSavedPaymentDetails($this->getUser()); 
        }

        return $this->render('account/order/approve-proof.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
            'data' => [
                'showPaymentOption' => $showPaymentOption,
                'totalAmount' => $totalAmount,
                'isLoggedIn' => $this->getUser() !== null,
            ],
            'messages' => [$approvedProof],
            'updateAddressForm' => $updateAddressForm,
            'amazonPay' => $amazonPayCheckoutData,
            'savedCards' => $savedCards,
        ]);
    }

    private function proofApprovedLog(Order $order, OrderMessage $approvedProof, OrderLogger $orderLogger, VichS3Helper $s3Helper): void
    {
        $orderLogger->setOrder($order);
        try {
            $image = '';
            $pdf = '';
            $user = $order->getUser();
            $userName = $user->getName() . ' <small>(' . $user->getUsername() . ')</small>';

            foreach ($approvedProof->getFiles() as $file) {
                if ($file->getType() == 'PROOF_IMAGE') {
                    $image = '<a href="' . $s3Helper->asset($file, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof Image</a>';
                } elseif ($file->getType() == 'PROOF_FILE') {
                    $pdf = '<a href="' . $s3Helper->asset($file, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof File</a>';
                }
            }
            $content = '<b>Proof Approved By:</b> ' . $userName . '
                        <br/>
                        <b>Payment Method:</b> ' . $order->getPaymentMethod() . '
                        <br/>
                        <b>Proof Approved At:</b> ' . (new \DateTimeImmutable())->format('M d, Y h:i:s A') . '
                        <br/>
                        <b>Comments:</b> ' . $approvedProof->getContent() . '
                        <br/>
                        ' . $image . ' | ' . $pdf . '';

            $orderLogger->log($content);
        } catch (\Exception $e) {
            $orderLogger->log($e->getMessage());
        }
    }
}
