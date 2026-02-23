<?php

namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\Admin\OrderItem\OrderItemType;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use App\Controller\Admin\Order\NewOrder\AddOns;

#[AsLiveComponent(
    name: "EditOrderItemForm",
    template: "admin/components/order/edit-item.html.twig"
)]
class EditOrderItemForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderItemRepository    $orderItemRepository,
        private readonly OrderRepository        $orderRepository,
        private readonly OrderService           $orderService
    )
    {
    }

    #[LiveProp]
    #[NotNull]
    public ?Order $order;

    #[LiveProp(fieldName: 'formData')]
    public ?OrderItem $orderItem;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrderItemType::class, $this->orderItem);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save(): Response
    {
        $originalOrderItemData = clone $this->orderItem;

        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->validate();

        $addons = $data->getAddOns() ?? [];
        $addonsInput = [
            'sides'        => $form->get('addons')->get('sides')->getData(),
            'shapes'       => $form->get('addons')->get('shapes')->getData(),
            'imprintColor' => $form->get('addons')->get('imprintColor')->getData(),
            'grommets'     => $form->get('addons')->get('grommets')->getData(),
            'grommetColor' => $form->get('addons')->get('grommetColor')->getData(),
            'frame'        => $form->get('addons')->get('frame')->getData(),
        ];

        $addons = AddOns::buildAddOns($addonsInput);
        $data->setAddOns($addons);

        try {
            $this->order->setSubTotalAmount(($this->order->getSubTotalAmount() - $originalOrderItemData->getTotalAmount()) + $data->getTotalAmount());
            $this->order->setTotalAmount(($this->order->getTotalAmount() - $originalOrderItemData->getTotalAmount()) + $data->getTotalAmount());

            $order = $this->orderService->updatePaymentStatus($this->order);

            if ($order->isIsManual()) {
                $data = $this->updateTemplateSize($data, $form->get('width')->getData(), $form->get('height')->getData());
            }

            $this->entityManager->persist($order);
            $this->entityManager->persist($data);
            $this->entityManager->flush();
            $this->addFlash('success', 'Order item updated successfully');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $this->order->getOrderId()]);
        }
    }

    public function updateTemplateSize(OrderItem $item, string $width, string $height): OrderItem
    {
        $templateSize = $width . 'x' . $height;
        $product = $item->getProduct();
        if ($product->getParent()) {
            $product = $product->getParent();
        }
        list($product, $variant) = $this->getProduct($product->getSku(), $templateSize);
        $item->setProduct($variant);

        $customSize = $item->getMetaDataKey('customSize');
        $customSize['closestVariant'] = $templateSize;
        $customSize['isCustomSize'] = str_contains($product->getSku(), 'CUSTOM-SIZE');
        $customSize['templateSize'] = [
            'width' => $width,
            'height' => $height,
        ];

        $item->setMetaDataKey('customSize', $customSize);
        $item->setMetaDataKey('isCustomSize', $customSize['isCustomSize']);
        return $item;
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
}