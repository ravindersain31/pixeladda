<?php

namespace App\Controller\Admin\Order;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\AdminFile;
use App\Entity\AppUser;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderLog;
use App\Entity\OrderMessage;
use App\Entity\OrderTransaction;
use App\Entity\Reward\RewardTransaction;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\ShippingStatusEnum;
use App\Event\OrderAdminPrintedAssignedEvent;
use App\Event\OrderAdminStatusChangeEvent;
use App\Event\OrderReceivedEmailEvent;
use App\Form\Admin\Customer\Reward\OrderRewardTransactionType;
use App\Form\Admin\Order\ChangeOrderNotesType;
use App\Form\Admin\Order\ChangeOrderStatusType;
use App\Form\Admin\Order\FilterOrderType;
use App\Form\Admin\Order\OrderTagsType;
use App\Form\Admin\Order\PrinterNameType;
use App\Form\Admin\Order\RepeatOrderFilterType;
use App\Form\Admin\Order\UpdateAddressType;
use App\Form\Admin\Order\UpdateBillingAddressType;
use App\Form\Admin\Order\UpdateCheckPoPaymentType;
use App\Form\Admin\Order\UpdateShippingAddressType;
use App\Form\Admin\Order\UploadPrintFileType;
use App\Helper\AddressHelper;
use App\Helper\PromoStoreHelper;
use App\Repository\AppUserRepository;
use App\Repository\FraudRepository;
use App\Repository\OrderItemRepository;
use App\Repository\OrderLogRepository;
use App\Repository\OrderRepository;
use App\Service\Admin\WarehouseEvents;
use App\Service\Admin\WarehouseService;
use App\Service\CartManagerService;
use App\Service\EasyPost\EasyPostEstimatedDelivery;
use App\Service\ExportService;
use App\Service\GoogleDriveService;
use App\Service\OrderDeliveryDateService;
use App\Service\OrderLogger;
use App\Service\OrderService;
use App\Service\Reward\RewardService;
use App\Service\SlackManager;
use App\Service\Ups\TimeInTransitPayload;
use App\Service\Ups\UpsTimeInTransitService;
use App\SlackSchema\AddressUpdatedSchema;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderController extends AbstractController
{
    #[Route('/orders/{status}', name: 'orders', defaults: ['status' => 'all'])]
    public function index(Request $request, OrderRepository $repository, PaginatorInterface $paginator, FraudRepository $fraudRepository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $status = $request->get('status');
        if ($status !== 'all') {
            $customPages = ['check-po', 'today-super-rush-order', 'order-protection-orders', 'fraud-orders', 'refunded', 'upload-proof', 'repeat-orders'];
            $statusKey = str_replace('-', '_', strtoupper($status));
            $validStatus = [...array_keys(OrderStatusEnum::LABELS), ...array_map(fn($page) => str_replace('-', '_', strtoupper($page)), $customPages)];
            if (!in_array($statusKey, $validStatus)) {
                throw $this->createNotFoundException('Status not found');
            }
            if (in_array($status, $customPages)) {
                $query = $repository->findOrderCustomPages($status);
            } else {
                $query = $repository->findByStatus($statusKey);
            }
        } else {
            $query = $repository->fetchAll();
        }

        $page = intval($request->get('page', 1));

        $allowOrderFilterPaymentOption = $this->isGranted('order_filter_payment_option');
        $filterForm = $this->createForm(FilterOrderType::class, null, [
            'method' => 'GET',
            'allow_extra_fields' => true,
            'action' => $this->generateUrl('admin_orders'),
            'hidePaymentOption' => !$allowOrderFilterPaymentOption,
        ]);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $searchValue = $filterForm->get('search')->getData() ? base64_decode($filterForm->get('search')->getData()) : null;
            if ($allowOrderFilterPaymentOption) {
                $query = $repository->filterOrder(
                    status: [$filterForm->get('status')->getData()],
                    fromDate: $filterForm->get('fromDate')->getData() ? new \DateTimeImmutable($filterForm->get('fromDate')->getData() . '00:00:00') : null,
                    endDate: $filterForm->get('endDate')->getData() ? new \DateTimeImmutable($filterForm->get('endDate')->getData() . '23:59:59') : null,
                    paymentStatus: $filterForm->get('paymentStatus')->getData(),
                    search: $searchValue,
                    canceledOrders: true,
                );
            } else {
                $query = $repository->filterOrder(
                    fromDate: $filterForm->get('fromDate')->getData() ? new \DateTimeImmutable($filterForm->get('fromDate')->getData() . '00:00:00') : null,
                    endDate: $filterForm->get('endDate')->getData() ? new \DateTimeImmutable($filterForm->get('endDate')->getData() . '23:59:59') : null,
                    search: $searchValue,
                    canceledOrders: true,
                );
            }
        }

        $orders = $paginator->paginate($query, $page, 20);

        $statusLabel = 'All';
        if ($status !== 'all') {
            if (in_array($status, $customPages)) {
                if ($status == 'fraud-orders') {
                    $page = $request->query->getInt('page', 1);
                    $frauds = $fraudRepository->fetchAll();
                    $fraudOrders = $repository->getFraudOrders($frauds);
                    $orders = $paginator->paginate($fraudOrders, $page, 20);
                }
                if ($status == 'repeat-orders') {
                    return $this->redirectToRoute('admin_orders_repeat_orders');
                }
                $labels = [
                    'check-po' => 'Check PO',
                    'today-super-rush-order' => 'Today Super Rush',
                    'order-protection-orders' => 'Order Protection',
                    'fraud-orders' => 'Fraud',
                    'refunded' => 'Refunded',
                    'upload-proof' => 'Upload Proof'
                ];
                $statusLabel = $labels[$status];
            } else {
                $statusLabel = OrderStatusEnum::LABELS[$statusKey] ?? 'All';
            }
        }
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'status' => $status,
            'statusLabel' => $statusLabel,
            'filterForm' => $filterForm->createView(),
            'isFraud' => $fraudRepository->isFraudOrder($orders, null),
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
            'allowOrderFilterPaymentOption' => $allowOrderFilterPaymentOption,
        ]);
    }

    #[Route('/orders/{orderId}/overview', name: 'order_overview')]
    public function orderView(string $orderId, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager, OrderLogger $orderLogger, FraudRepository $fraudRepository, EventDispatcherInterface $eventDispatcher): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $orderNotesForm = $this->createForm(ChangeOrderNotesType::class, null, ['order' => $order]);
        $orderNotesForm->handleRequest($request);
        if ($orderNotesForm->isSubmitted() && $orderNotesForm->isValid()) {
            $adminStatusChangeEvent = new OrderAdminStatusChangeEvent($order);
            $eventDispatcher->dispatch($adminStatusChangeEvent, OrderAdminStatusChangeEvent::NAME);
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $orderStatusForm = $this->createForm(ChangeOrderStatusType::class, null, ['order' => $order]);
        $orderStatusForm->handleRequest($request);
        if ($orderStatusForm->isSubmitted() && $orderStatusForm->isValid()) {
            $adminStatusChangeEvent = new OrderAdminStatusChangeEvent($order);
            $eventDispatcher->dispatch($adminStatusChangeEvent, OrderAdminStatusChangeEvent::NAME);
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $orderTags = $this->createForm(OrderTagsType::class, $order, [
            'metaData' => $order->getMetaData()
        ]);
        $orderTags->handleRequest($request);

        if ($orderTags->isSubmitted() && $orderTags->isValid()) {
            $repository->save($order, true);
            $this->addFlash('success', 'Order tags updated successfully');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $printerNameForm = $this->createForm(PrinterNameType::class, $order);
        $printerNameForm->handleRequest($request);
        if ($printerNameForm->isSubmitted() && $printerNameForm->isValid()) {
            $orderLogger->setOrder($order);
            $adminOrderPrinterAssigned = new OrderAdminPrintedAssignedEvent($order);
            $eventDispatcher->dispatch($adminOrderPrinterAssigned, OrderAdminPrintedAssignedEvent::NAME);
            $orderLogger->log('Printer Name updated to <b>' . $order->getPrinterName() . '</b>', $this->getUser(), OrderLog::TYPE_INTERNAL);
            $repository->save($order, true);
            $this->addFlash('success', 'Printer Name updated successfully');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $messages = $entityManager->getRepository(OrderMessage::class)->getPrintCutFile($order);

        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
            'isFraud' => $fraudRepository->isFraudOrder(null, $order),
            'orderStatusForm' => $orderStatusForm->createView(),
            'orderNotesForm' => $orderNotesForm->createView(),
            'printerNameForm' => $printerNameForm->createView(),
            'orderTags' => $orderTags->createView(),
            'messages' => $messages
        ]);
    }

    #[Route('/orders/{orderId}/update-address/{type}', name: 'order_update_address')]
    public function updateAddress(string $orderId, string $type, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager, OrderLogger $orderLogger, FraudRepository $fraudRepository, EventDispatcherInterface $eventDispatcher, SlackManager $slack): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $currentAddress = $order->getAddress($type);
        $form = $this->createForm(UpdateAddressType::class, null, ['order' => $order, 'addressType' => $type])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newAddress = $data[$type];
            $order->setAddress($type, $newAddress);
            $readableType = ucwords(preg_replace('/(?<!^)([A-Z])/', ' $1', $type));

            $isAddressUpdated = AddressHelper::isAddressUpdated($currentAddress, $newAddress);
            if ($isAddressUpdated) {
                $order->setMetaDataKey('ep' . ucfirst($type), null);
                $message = sprintf(
                    "%s Updated\n%s",
                    $readableType,
                    AddressHelper::formatAddressChange($currentAddress, $newAddress)
                );

                $slack->send(SlackManager::ADDRESS_CHANGE, AddressUpdatedSchema::get($order, $type, $currentAddress, $newAddress));
                $orderLogger->setOrder($order);
                $orderLogger->log(content: $message, type: OrderLog::ORDER_ADDRESS_UPDATED);
            }

            if (isset($data['textUpdates']) && $data['textUpdates']) {
                $order->setTextUpdates(true);
                $order->setTextUpdatesNumber($data['textUpdatesNumber']);
            } else {
                $order->setTextUpdates(false);
                $order->setTextUpdatesNumber(null);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', $readableType . ' updated successfully.');
            $entityManager->refresh($order);
        } else {
            $this->addFlash('danger', 'Address update failed. Please see the errors in "Edit"');
        }
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
    }

    #[Route('/orders/{orderId}/logs', name: 'order_logs')]
    public function logs(string $orderId, Request $request, OrderRepository $repository, OrderLogRepository $logRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $page = $request->query->getInt('page', 1);
        $logsQuery = $logRepository->getOrderLogs($order);
        $logs = $paginator->paginate($logsQuery, $page, 20);

        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
            'logs' => $logs,
        ]);
    }

    #[Route('/orders/{orderId}/related-orders', name: 'order_related_orders')]
    public function relatedOrder(string $orderId, Request $request, OrderRepository $repository, PaginatorInterface $paginator, FraudRepository $fraudRepository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $orderUser = $order->getUser();
        if (!$orderUser instanceof AppUser) {
            throw $this->createNotFoundException('No user associated with this order.');
        }

        $shippingEmail = $order->getShippingAddress()['email'] ?? null;
        $billingEmail  = $order->getBillingAddress()['email'] ?? null;

        $page = $request->query->getInt('page', 1);
        $orderQuery = $repository->findRelatedOrderByCustomer($orderUser, $shippingEmail, $billingEmail, false);
        $orders = $paginator->paginate($orderQuery, $page, 20);

        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
            'status' => 'all',
            'orders' => $orders,
            'isFraud' => $fraudRepository->isFraudOrder($orders, null),
        ]);
    }

    #[Route('/orders/{orderId}/assign-to-me', name: 'order_assign_to_me')]
    public function assignToMe(string $orderId, Request $request, OrderRepository $repository, OrderLogger $orderLogger): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $user = $this->getUser();
        $order = $repository->findByOrderId($orderId);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        if ($order->getStatus() === OrderStatusEnum::PROOF_APPROVED) {
            $this->addFlash('warning', 'You can\'t assign the order as the proof has already been <b>APPROVED</b> for this order.');
            return $this->redirectToRoute('admin_order_proofs', ['orderId' => $orderId]);
        }

        if ($order->getProofDesigner() === $user) {
            $orderMessages = $order->getOrderMessages();
            $order->setProofDesigner(null);
            $order->setStatus(OrderStatusEnum::RECEIVED);

            if (!$orderMessages->isEmpty()) {
                $lastMessage = $orderMessages->last();
                if ($lastMessage && $lastMessage->getType() === OrderStatusEnum::CHANGES_REQUESTED) {
                    $order->setStatus(OrderStatusEnum::CHANGES_REQUESTED);
                }
            }

            $orderLogger->setOrder($order);
            $orderLogger->log(
                sprintf('This order has been unassigned by <b>%s</b>.', $user->getUserIdentifier()),
                $user,
                OrderLog::TYPE_INTERNAL
            );

            $this->addFlash('info', 'You have unassigned yourself from this order.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
        }

        $existingOrder = $repository->findAssignedOrderByUser($user);

        if ($existingOrder) {
            $this->addFlash('danger', 'You already have an assigned order that is not yet completed.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
        }

        $order->setProofDesigner($user);
        $order->setStatus(OrderStatusEnum::DESIGNER_ASSIGNED);

        $orderLogger->setOrder($order);
        $orderLogger->log(
            sprintf('This order has been assigned to <b>%s</b> for designing the proof.', $user->getUserIdentifier()),
            $user,
            OrderLog::TYPE_INTERNAL
        );

        $this->addFlash('success', 'This order has been assigned to you.');

        return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
    }

    #[Route('/orders/{orderId}/repeat-order', name: 'order_repeat_order')]
    public function repeatOrder(string $orderId, Request $request, OrderRepository $repository, OrderLogger $orderLogger, CartManagerService $cartManagerService, OrderService $orderService, PromoStoreHelper $promoStoreHelper): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        if ($order->isIsManual()) {
            $newOrder = $orderService->deepCloneOrder($order);
            $orderLogger->setOrder($order);
            $message = 'New Order ID ' . $newOrder->getOrderId() . ' has been created from Order ID ' . $order->getOrderId() . ' and the order channel is ' . $order->getOrderChannel()->label();
            if ($order->getParent()) {
                $message .= ' and this is the sub order of Order ID ' . $order->getParent()->getOrderId();
            }
            $orderLogger->log($message);
            $repository->save($newOrder, true);
            $this->addFlash('success', 'Repeat Order has been created.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $newOrder->getOrderId()]);
        }

        $cart = $order->getCart();
        $repeatCart = $cartManagerService->deepClone($cart, isRepeatOrder: true, order: $order);

        $cartUrl = $this->generateUrl('cart', ['id' => $repeatCart->getCartId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $cartUrl = $promoStoreHelper->storeBasedUrl($cartUrl, $order->getStoreDomain());
        $orderLogger->setOrder($order);
        $orderLogger->log('Repeat Order has been created. <a href="' . $cartUrl . '" target="_blank">Click here</a> to visit cart');
        return $this->redirect($cartUrl);
    }

    #[Route('/orders/{orderId}/send-invoice', name: 'order_send_invoice')]
    public function sentOrderInvoice(#[MapEntity(mapping: ['orderId' => 'orderId'])]  Order $order, Request $request, EventDispatcherInterface $eventDispatcher): Response
    {
        try {

            $this->denyAccessUnlessGranted($request->get('_route'));
            $eventDispatcher->dispatch(new OrderReceivedEmailEvent($order), OrderReceivedEmailEvent::NAME);
            $this->addFlash('success', 'Invoice has been successfully send on the customer email address.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Something went wrong. ' . $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/repeat-orders/', name: 'orders_repeat_orders')]
    public function repeatOrders(Request $request, OrderRepository $repository, PaginatorInterface $paginator): Response
    {
        $todayDateTime = new \DateTimeImmutable('now');

        $repeatOrderFilter = $this->createForm(RepeatOrderFilterType::class, [
            'method' => 'GET'
        ]);
        $repeatOrderFilter->handleRequest($request);
        if ($repeatOrderFilter->isSubmitted() && $repeatOrderFilter->isValid()) {
            $query = $repository->findOrdersGroupedByUser(
                fromDate: new \DateTimeImmutable($repeatOrderFilter->get('date')->getData()->format('Y-m-d') . '00:00:00'),
                endDate: new \DateTimeImmutable($repeatOrderFilter->get('date')->getData()->format('Y-m-d') . '23:59:59'),
            );
        } else {
            $query = $repository->findOrdersGroupedByUser(
                fromDate: $todayDateTime->setTime(00, 00, 00),
                endDate: $todayDateTime->setTime(23, 59, 59),
            );
        }

        $page = $request->query->getInt('page', 1);
        $users = $paginator->paginate($query, $page, 20);
        return $this->render('admin/order/view/_repeat_orders.html.twig', [
            'users' => $users,
            'repeatOrderFilter' => $repeatOrderFilter,
            'isFilterFormSubmitted' => $repeatOrderFilter->isSubmitted() && $repeatOrderFilter->isValid(),
        ]);
    }

    #[Route('/export/{orderId}/details', name: 'export_order_details')]
    public function exportCsv(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, ExportService $exportService): Response
    {
        try {

            $filename = 'order_' . date('YmdHis') . '.xlsx';
            $exportService->exportOrder($order, $filename);
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/orders/remove/{orderId}/{itemId}/{status}', name: 'order_item_remove')]
    public function removeItem($orderId, $itemId, $status, OrderRepository $repository, OrderItemRepository $itemRepository, OrderService $orderService): Response
    {
        try {
            $orderItem = $itemRepository->findOneBy(['id' => $itemId]);
            $order = $repository->findByOrderId($orderId);
            if (!$orderItem instanceof OrderItem) {
                throw $this->createNotFoundException('Order item not found');
            }
            if (!in_array($orderItem->getItemType(), [OrderItem::ITEMSTATUS['CHARGED_ITEM'], OrderItem::ITEMSTATUS['DISCOUNT_ITEM'], OrderItem::COMMENT_ITEM])) {
                throw $this->createNotFoundException('Item not found');
            }
            if ($orderItem->getItemType() === OrderItem::ITEMSTATUS['CHARGED_ITEM']) {
                $order->setSubTotalAmount($order->getSubTotalAmount() - $orderItem->getPrice());
                $order->setTotalAmount($order->getTotalAmount() - $orderItem->getTotalAmount());
                $this->addFlash('success', 'Charged item removed successfully.');
            } else if ($orderItem->getItemType() === OrderItem::ITEMSTATUS['DISCOUNT_ITEM']) {
                $order->setSubTotalAmount($order->getSubTotalAmount() + $orderItem->getPrice());
                $order->setTotalAmount($order->getTotalAmount() + $orderItem->getTotalAmount());
                $this->addFlash('success', 'Discount item removed successfully.');
            } else if ($orderItem->getItemType() === OrderItem::COMMENT_ITEM) {
                $this->addFlash('success', 'Comment removed successfully.');
            }

            $order = $orderService->updatePaymentStatus($order);

            $repository->save($order, true);
            $itemRepository->remove($orderItem, true);

            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/orders/{order}/{coupon}/remove', name: 'order_coupon_remove')]
    public function orderCouponRemove(Order $order, OrderRepository $repository, OrderLogger $orderLogger, OrderService $orderService): Response
    {
        try {
            $coupon = $order->getCoupon();
            $dicountAmount = $order->getCouponDiscountAmount();
            $order->setCoupon(null);
            $order->setTotalAmount($order->getTotalAmount() + $order->getCouponDiscountAmount());
            $order->setCouponDiscountAmount(0);

            $orderLogger->setOrder($order);
            $orderLogger->log('Removed coupon "' . $coupon->getCode() . '" (discount: $' . number_format($dicountAmount, 2) . ') from order ID ' . $order->getOrderId());

            $order = $orderService->updatePaymentStatus($order);

            $this->addFlash('success', 'Coupon removed successfully.');
            $repository->save($order, true);

            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/orders/{order}/removeProtection', name: 'order_protection_remove')]
    public function orderProtectionRemove(Order $order, OrderRepository $repository, OrderLogger $orderLogger, OrderService $orderService): Response
    {
        try {
            $prevOrderProtection = $order->getOrderProtectionAmount();
            $order->setTotalAmount($order->getTotalAmount() - $order->getOrderProtectionAmount());
            $order->setOrderProtectionAmount(0);

            $order = $orderService->updatePaymentStatus($order);

            $repository->save($order, true);
            $this->addFlash('success', 'Order Protection is removed successfully.');
            $orderLogger->setOrder($order);
            $orderLogger->log('Order Protection of $' . $prevOrderProtection . ' is removed.', $this->getUser());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/orders/{order}/removeInternationalShippingCharge', name: 'international_shipping_charge_remove')]
    public function internationalShippingChargeRemove(Order $order, OrderRepository $repository, OrderLogger $orderLogger, OrderService $orderService): Response
    {
        try {
            $prevInternationalShippingCharge = $order->getInternationalShippingChargeAmount();
            $order->setTotalAmount($order->getTotalAmount() - $order->getInternationalShippingChargeAmount());
            $order->setInternationalShippingChargeAmount(0);
            $order = $orderService->updatePaymentStatus($order);
            $repository->save($order, true);
            $this->addFlash('success', 'International Shipping Charge is removed successfully.');
            $orderLogger->setOrder($order);
            $orderLogger->log('International Shipping Charge of $' . $prevInternationalShippingCharge . ' is removed.', $this->getUser());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/orders/{order}/removeShipping', name: 'order_shipping_remove')]
    public function orderShippingRemove(Order $order, OrderDeliveryDateService $orderDeliveryDateService, OrderLogger $orderLogger, OrderService $orderService): Response
    {
        try {
            $previousDeliveryData = $order->getDeliveryDate();
            $orderDeliveryDateService->applyFreeShipping($order);
            $newDeliveryData = $order->getDeliveryDate();
            $this->addFlash('success', 'Shipping Fees removed successfully.');
            $order = $orderService->updatePaymentStatus($order);
            $orderLogger->setOrder($order);
            $orderLogger->log('Delivery Date has been updated from ' . $previousDeliveryData->format('Y-m-d') . ' to ' . $newDeliveryData->format('Y-m-d'), $this->getUser());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/orders/{order}/removeFreeFreight', name: 'order_free_freight_remove', methods: ['GET'])]
    public function orderFreeFreightRemove(Order $order, OrderLogger $orderLogger, OrderService $orderService): Response 
    {
        try {
            $orderService->removeFreeFreight($order);

            $orderLogger->setOrder($order);
            $orderLogger->log(
                '<b>Free Freight</b> removed from order items.',
                $this->getUser()
            );

            $this->addFlash('success', 'Free Freight removed successfully.');

        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_order_overview', [
            'orderId' => $order->getOrderId()
        ]);
    }

    #[Route('/orders/{order}/removeBlindShipping', name: 'order_blind_shipping_remove', methods: ['GET'])]
    public function orderBlindShippingRemove(Order $order, OrderLogger $orderLogger, OrderService $orderService): Response 
    {
        try {
            $orderService->removeBlindShipping($order);

            $orderLogger->setOrder($order);
            $orderLogger->log(
                '<b>Blind Shipping</b> removed from order items.',
                $this->getUser()
            );

            $this->addFlash('success', 'Blind Shipping removed successfully.');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_order_overview', [
            'orderId' => $order->getOrderId()
        ]);
    }

    #[Route('/orders/{order}/removeRequestPickup', name: 'order_request_pickup_remove', methods: ['GET'])]
    public function orderRequestPickupRemove(Order $order, OrderLogger $orderLogger, OrderService $orderService): Response 
    {
        try {
            $orderService->removeRequestPickup($order);
            $orderLogger->setOrder($order);
            $orderLogger->log(
                '<b>Request Pickup</b> removed from order items.',
                $this->getUser()
            );

            $this->addFlash('success', 'Request Pickup removed successfully.');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('admin_order_overview', [
            'orderId' => $order->getOrderId()
        ]);
    }

    #[Route('/orders/{orderId}/update-payment', name: 'order_update_payment')]
    public function updatePayment(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $orderUser = $order->getUser();
        if (!$orderUser instanceof AppUser) {
            throw $this->createNotFoundException('No user associated with this order.');
        }

        $form = $this->createForm(UpdateCheckPoPaymentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $transaction = new OrderTransaction();
            $transaction->setOrder($order);
            $transaction->setStatus(PaymentStatusEnum::COMPLETED);
            $transaction->setAmount($order->getTotalAmount());
            $transaction->setCurrency($data['currency']);
            $transaction->setPaymentMethod(PaymentMethodEnum::CHECK);
            $transaction->setGatewayId('Offline Payment');
            $transaction->setComment($data['comment']);

            $proofFileFile = $form->getData()['proofFile'];
            $proofFile = null;

            if ($proofFileFile) {
                $proofFile = $this->handleFileUpload($proofFileFile, AdminFile::FILE_TYPE['CHECKPO_PROOF'], $this->getUser(), $entityManager);
            }

            $transaction->setProofFile($proofFile);

            $transaction->setMetaDataKey('paymentReferenceNumber', $data['refNumber']);
            $transaction->setMetaDataKey('poNumber', $data['poNumber']);
            $transaction->setMetaDataKey('otherDetails', $data['comment']);
            $entityManager->persist($transaction);

            $order->setPaymentStatus(PaymentStatusEnum::COMPLETED);
            $order->setPaymentMethod(PaymentMethodEnum::CHECK);
            $order->setTotalReceivedAmount($order->getTotalAmount());
            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Payment details updated successfully.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/orders/{orderId}/reward-history', name: 'order_reward_history')]
    public function rewardHistory(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, Request $request, RewardService $rewardService): Response
    {
        try {

            $this->denyAccessUnlessGranted($request->get('_route'));
            if ($order->getUser()) {
                $rewardService->getOrCreateReward($order->getUser());
            }

            $rewardTransaction = new RewardTransaction();

            $form = $this->createForm(OrderRewardTransactionType::class, $rewardTransaction);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                $rewardService->updateRewardPoints(
                    reward: $order->getUser()->getReward(),
                    points: $data->getPoints(),
                    comment: $data->getComment(),
                    type: RewardTransaction::CREDIT,
                    user: $this->getUser(),
                    status: RewardTransaction::STATUS_COMPLETED,
                    actionType: RewardTransaction::ADMIN_CREDIT_CUSTOMER_REWARDS,
                    order: $order,
                );
                $this->addFlash('success', 'Reward has been added successfully.');
                return $this->redirectToRoute('admin_order_reward_history', ['orderId' => $order->getOrderId()]);
            }

            return $this->render('admin/order/view.html.twig', [
                'order' => $order,
                'rewardForm' => $form,
                'rewardTransaction' => $rewardTransaction
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview');
        }
    }

    #[Route('/orders/{orderId}/move-to-ready-for-production', name: 'order_move_to_ready_for_production')]
    public function moveToReadyForSE(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, EntityManagerInterface $entityManager, WarehouseService $warehouseService, OrderLogger $logger, UpsTimeInTransitService $estimatedDelivery): Response
    {

        if (!in_array($order->getStatus(), [OrderStatusEnum::PROOF_APPROVED])) {
            $this->addFlash('danger', 'Order is not in Proof Approved status');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $epAddress = $order->getMetaDataKey('epShippingAddress');
        if (!$epAddress || (isset($epAddress['id']) && !$epAddress['id'])) {
            $this->addFlash('danger', 'Please validate EP shipping address first.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        // $timeInTransitPayload = new TimeInTransitPayload(
        //     originCountryCode: 'US',
        //     originPostalCode: ,
        //     destinationCountryCode: $epAddress['country'],
        //     destinationStateProvince: $epAddress['state'],
        //     destinationPostalCode: $epAddress['zip'],
        // );

        // $daysInTransit = $estimatedDelivery->retrieveDaysInTransit($timeInTransitPayload);
        // $order->setMetaDataKey('epDaysInTransit', $daysInTransit);

        $order->setStatus(OrderStatusEnum::SENT_FOR_PRODUCTION);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($order);
        $entityManager->flush();

        if (!$order->getWarehouseOrder() instanceof WarehouseOrder) {
            $user = $this->getUser();
            $warehouseService->setAdminUser($user);
            $warehouseService->getWarehouseOrder($order);
        }

        $logger->setOrder($order);
        $logger->log('Order has been moved to Ready for Production', $this->getUser());

        $this->addFlash('success', 'Order has been moved to Ready for Production');
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
    }

    private function handleFileUpload($file, $type, $user, $entityManager): AdminFile
    {
        $adminFile = new AdminFile();
        $adminFile->setFileObject($file);
        $adminFile->setType($type);
        $adminFile->setUploadedBy($user);
        $entityManager->persist($adminFile);

        return $adminFile;
    }

    #[Route('/orders/{orderId}/re-queue-order', name: 're_queue_order')]
    public function reQueueOrder(
        #[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order,
        EntityManagerInterface $entityManager,
        WarehouseService $warehouseService,
        OrderLogger $logger,
        WarehouseEvents $warehouseEvents
    ): Response {
        $warehouseOrder = $order->getWarehouseOrder();
        if (!$warehouseOrder instanceof WarehouseOrder) {
            $this->addFlash('danger', 'Order is not in Order Queue');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        if ($warehouseOrder->getPrintStatus() !== WarehouseOrderStatusEnum::DONE) {
            $this->addFlash('info', 'Order already in Warehouse Order Queue');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::READY);
        $entityManager->persist($warehouseOrder);
        $warehouseService->addWarehouseOrderLog($warehouseOrder, 'Order added to Warehouse Order Queue');
        $warehouseService->activateShipByList($warehouseOrder);

        $warehouseEvents->createOrUpdateShipByListEvent(shipByList: [$warehouseOrder->getShipBy()->format('Y-m-d')], printer: $warehouseOrder->getPrinterName());

        $logger->setOrder($order);

        if ($order->getStatus() !== OrderStatusEnum::READY_FOR_SHIPMENT) {
            $order->setStatus(OrderStatusEnum::SENT_FOR_PRODUCTION);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($order);
            $logger->log('Order has been moved to Ready for Production', $this->getUser());
            $this->addFlash('success', 'Order has been moved to Ready for Production');
        }

        $logger->log('Order added to Warehouse Order Queue', $this->getUser());
        $entityManager->flush();

        $this->addFlash('success', 'Order added to Warehouse Order Queue');
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
    }

    #[Route('/orders/{orderId}/split-order', name: 'order_split_order')]
    public function splitOrder(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, Request $request, PaginatorInterface $paginator): Response
    {
        try {

            $page = intval($request->get('page', 1));

            $orders = $paginator->paginate($order->getSubOrders()->filter(fn(Order $order) => $order->getOrderChannel() === OrderChannelEnum::SPLIT_ORDER), $page, 20);
            return $this->render('admin/order/view.html.twig', [
                'orders' => $orders,
                'order' => $order,
                'status' => 'split',
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview');
        }
    }

    #[Route('/orders/{orderId}/is-paused', name: 'order_is_paused')]
    public function isPaused(#[MapEntity(mapping: ['orderId' => 'orderId'])] Order $order, Request $request, EntityManagerInterface $entityManager, WarehouseEvents $warehouseEvents): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $order->setIsPause(!$order->isPause());
        $entityManager->persist($order);
        $entityManager->flush();

        if ($order->getWarehouseOrder() instanceof WarehouseOrder) {
            $order->getWarehouseOrder()->setPrintStatus(
                $order->isPause() ? WarehouseOrderStatusEnum::PAUSED : WarehouseOrderStatusEnum::READY
            );
            $entityManager->persist($order->getWarehouseOrder());
            $entityManager->flush();
            $warehouseEvents->updatePrintStatusEvent($order->getWarehouseOrder());
        }

        if ($order->isPause()) {
            $this->addFlash('success', 'Order is paused successfully.');
        } else {
            $this->addFlash('success', 'Order is resumed successfully.');
        }

        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
    }

    #[Route('/orders/{orderId}/mark-as-completed', name: 'order_mark_as_completed')]
    public function markAsCompleted(#[MapEntity(mapping: ['orderId' => 'orderId'])]  Order $order, Request $request, EntityManagerInterface $entityManager, OrderLogger $orderLogger): Response
    {
        try {
            $this->denyAccessUnlessGranted($request->get('_route'));
            if (in_array($order->getStatus(), [OrderStatusEnum::CANCELLED, OrderStatusEnum::COMPLETED])) {
                $this->addFlash('danger', 'Order is already ' . strtolower($order->getStatus()));
                return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
            }
            $order->setStatus(OrderStatusEnum::COMPLETED);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $orderLogger->setOrder($order);
            $orderLogger->log('The order has been marked as completed');
            $this->addFlash('success', 'Order has been marked as completed.');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Something went wrong. ' . $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }
    }

    #[Route('/api/orders/{orderNumber}/create-drive-folder', name: 'api_create_order_drive_folder', methods: ['POST'])]
    public function createDriveFolder(string $orderNumber, GoogleDriveService $driveService): JsonResponse
    {
        try {
            $driveLink = $driveService->createOrderFolder($orderNumber);
            return $this->json(['success' => true, 'driveLink' => $driveLink]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

}
