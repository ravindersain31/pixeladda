<?php
namespace App\Component\Admin\Order;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderStatusEnum;
use App\Form\Admin\Order\SplitOrder\SplitOrderType;
use App\Service\Admin\WarehouseService;
use App\Service\OrderLogger;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent(
	name: "AdminOrderSplitOrder",
	template: "admin/components/order/split-order.html.twig"
)]
class AdminOrderSplitOrder extends AbstractController
{
	use DefaultActionTrait;
	use LiveCollectionTrait;
	use ComponentWithFormTrait;
	use ValidatableComponentTrait;
	use ComponentToolsTrait;


	#[LiveProp]
	public ?string $flashMessage = null;
	public ?string $flashError = 'success';
	public bool $isSuccessful = false;

	#[LiveProp]
	#[NotNull]
	public ?Order $order;

	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly OrderService $orderService,
		private readonly WarehouseService $warehouseService,
		private readonly OrderLogger $logger
	){}

	protected function instantiateForm(): FormInterface
	{
		return $this->createForm(SplitOrderType::class, [], [
			'order' => $this->order
		]);
	}
	public function hasValidationErrors(): bool
	{
		return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
	}

	#[LiveAction]
	public function save(): Response
	{
		$this->validate();
		$this->submitForm();
		$form = $this->getForm();
		$data = $form->getData();
		try {

			if($this->order->getStatus() !== OrderStatusEnum::SENT_FOR_PRODUCTION){
				$this->addFlash('danger', 'Order status must be Ready for Production to split');
				return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
			}

			$baseOrderId = $this->order->getOrderId();
			$counter = $this->getNextCounter($baseOrderId);

			foreach ($data['subOrders'] as $order) {
				$newOrder = $this->cloneOrderOnly($this->order);
				$newOrder->setOrderId($this->generateOrderId($baseOrderId, $counter));
				$counter++;
				$newOrder = $this->addItemsToOrder($newOrder, $order['subOrderItems']);
				$newOrder = $this->updateTotal($newOrder);
				$newOrder->setShippingAmount(0);
				$newOrder->setOrderProtectionAmount(0);
				$newOrder->setCouponDiscountAmount(0);
				$newOrder->setTotalReceivedAmount(0);
				$newOrder->setSubTotalAmount(0);
				$newOrder->setOrderChannel(OrderChannelEnum::SPLIT_ORDER);

				$customTags['SPLIT_ORDER'] = [
					'name' => 'Split Order',
					'active' => true,
				];

				$newOrder->setMetaDataKey('tags', $customTags);

				$newOrder->setWarehouseOrder($this->addWarehouseOrder($newOrder));

				$this->entityManager->persist($newOrder);
				$this->entityManager->flush();
				$this->order->addSubOrder($newOrder);
				$this->entityManager->persist($this->order);
				$this->entityManager->flush();
			}

			$this->addFlash('success', 'Order splited successfully');
			return $this->redirectToRoute('admin_order_split_order', ['orderId' => $this->order->getOrderId()]);

		}catch(\Exception $e){
			$this->addFlash('danger', $e->getMessage());
			return $this->redirectToRoute('admin_order_split_order', ['orderId' => $this->order->getOrderId()]);
		}
	}

	private function updateTotal(Order $order): Order
	{
		$items = $order->getOrderItems();
		$subTotal = 0;
		foreach ($items as $item) {
			$subTotal += $item->getTotalAmount();
		}
		$order->setSubTotalAmount($subTotal);

		$order->setTotalAmount(0);
		return $order;

	}

	private function addWarehouseOrder(Order $order): WarehouseOrder
	{
		$warehouseOrder = new WarehouseOrder();
		$warehouseOrder->setOrder($order);
		$warehouseOrder->setPrinterName($order->getPrinterName());
		$warehouseOrder->setPrintStatus(WarehouseOrderStatusEnum::READY);
		$this->entityManager->persist($warehouseOrder);
		$this->entityManager->flush();
		$this->warehouseService->addWarehouseOrderLog($warehouseOrder, 'Order added to Warehouse Order Queue');

		return $warehouseOrder;
	}

	private function addItemsToOrder(Order $order, array $items): Order
	{
		foreach ($items as $item) {

			$templateSize = $item['width'] . 'x' . $item['height'];
			$orderItem = new OrderItem();
			$orderItem->setItemName($item['name']);
			$orderItem->setItemType('DEFAULT');
			$orderItem->setOrder($order);
			$orderItem->setCanvasData(['front' => [], 'back' => []]);
			list($product, $variant) = $this->getProduct($item['name'], $templateSize);
			$orderItem->setProduct($variant);

			$orderItem->setAddOns([]);

			$orderItem->setMetaData([]);

			$orderItem->setAddOnsAmount(0);
			$orderItem->setPrice(floatval(0));
			$orderItem->setQuantity(intval($item['quantity']));

			$orderItem->setUnitAmount(0);

			$orderItem->setTotalAmount(0);
			$order->addOrderItem($orderItem);
		}

		return $order;
	}

	private function getProduct(string $sku, string $templateSize): array
	{
		$product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
		if ($product) {
			$variant = $this->entityManager->getRepository(Product::class)->findOneBy(['parent' => $product, 'name' => $templateSize]);
			if ($variant) {
				return [$product, $variant];
			}
		}
		$product = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => 'CUSTOM-SIZE/01']);
		return [$product->getParent(), $product];
	}


	public function cloneOrderOnly(Order $order): Order
	{
		$newOrder = clone $order;
		return $newOrder;
	}
	

	public function getNextCounter(string $baseOrderId): int
	{
		// Find all orders starting with the baseOrderId
		$existingOrders = $this->entityManager->getRepository(Order::class)->findOrderByOrderId($baseOrderId);

		// Extract counters from order IDs
		$counters = [];
		foreach ($existingOrders as $order) {
			$orderId = $order->getOrderId();

			// Extract the counter part from the order ID
			if (preg_match('/^' . preg_quote($baseOrderId, '/') . '-(\d+)$/', $orderId, $matches)) {
				$counters[] = (int) $matches[1];
			}
		}

		// Return the next counter value
		return empty($counters) ? 1 : max($counters) + 1;
	}

	public function generateNewOrderId(string $baseOrderId): string
	{
		return $this->getNextCounter($baseOrderId);
	}

	public function generateOrderId(string $baseOrderId, int $counter): string
	{
		return $baseOrderId . '-' . $counter;
	}
}