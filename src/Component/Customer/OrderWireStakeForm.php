<?php

namespace App\Component\Customer;

use App\Constant\Editor\Addons;
use App\Constant\HomePageBlocks;
use App\Form\Page\OrderWireStakeType;
use App\Helper\OrderWireStakeHelper;
use App\Helper\ProductConfigHelper;
use App\Helper\ShippingChartHelper;
use App\Service\CartManagerService;
use App\Service\CartPriceManagerService;
use App\Twig\LightCartProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\PostPersist;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "OrderWireStakeForm",
    template: "components/order-wire-stake/index.html.twig"
)]
class OrderWireStakeForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    #[LiveProp]
    public ?array $product;

    #[LiveProp]
    public ?array $editData;

    private int $totalQty = 0;

    private float $subTotalAmount = 0;

    private float $totalAmount = 0;

    private array $variants = [];

    private array $updatedShipping = [];

    public array $data = [
        'items' => [],
        'totalQty' => 0,
        'subTotalAmount' => 0,
        'totalAmount' => 0,
        'shipping' => '',
        'shippingCost' => 0
    ];

    #[LiveProp(writable: true)]
    public bool $toggle = false;

    #[LiveProp(writable: true)]
    public bool $isBlindShipping = false;

    public array $shipping = [
        'day' => 5,
        'date' => '',
        'amount' => 0,
        'discount' => 0,
        'discountAmount' => 0,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface        $mailer,
        private readonly RequestStack           $requestStack,
        private readonly HomePageBlocks         $homePageBlocks,
        private readonly OrderWireStakeHelper   $orderWireStakeHelper,
        private readonly ShippingChartHelper    $shippingChartHelper,
        private readonly ProductConfigHelper    $productConfigHelper,
        private readonly LightCartProvider      $lightCartProvider,
        private readonly CartManagerService     $cartManagerService,
        private readonly CartPriceManagerService $cartPriceManagerService
    )
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrderWireStakeType::class, [], [
            'variants' => $this->product['variants'],
            'editData' => $this->editData,
            'product' => $this->product
        ]);
    }

    public function totalAmount(): float
    {
        return $this->totalAmount;
    }

    public function totalQuantity(): int {
        $this->calculateData();
        return $this->totalQty;
    }

    public function updateShipping(): array {
        return $this->updatedShipping;
    }

    public function subTotal(): float {
        return $this->subTotalAmount;
    }

    public function toggle(): bool {
        $this->toggle =  $this->form->get('pickup')->getData() ?? false;
        return $this->toggle;
    }

    public function isBlindShipping(): bool
    {
        $this->isBlindShipping = $this->form->get('isBlindShipping')->getData() ?? false;
        return $this->isBlindShipping;
    }

    public function getCurrentEditItem(): array
    {
        return $this->editData ? end($this->editData['items']) ?? [] : [];
    }

    public function isEdit(): bool
    {
        return !empty($this->editData) ? true : false;
    }

    public function getCurrentEditItemQuantity($frameName): int
    {
        if(isset($this->getCurrentEditItem()['data']['name']) && $this->getCurrentEditItem()['data']['name'] === $frameName){
            return $this->getCurrentEditItem()['data']['quantity'];
        }
        return 0;
    }

    public function getCurrentItemQuantity($itemName): int
    {
        return $this->getCartOverview()['quantityBySizes'][$itemName] ?? 0;
    }

    public function getCartOverview(): array
    {
        return $this->cartManagerService->getCartOverview($this->cartManagerService->getCart());
    }

    public function getVariantLabelName($frameType): string
    {
        $filteredVariants = array_filter($this->product['variants'], function($variant) use ($frameType) {
            return $variant['name'] === $frameType;
        });

        $variant = reset($filteredVariants);

        return $variant ? $variant['label'] : '';
    }

    public function getQuantityBySizes($frameType): int
    {
        return $this->getCurrentItemQuantity($frameType) ?? 0;
    }

    private function calculateData(): void
    {
        $this->variants = $this->form->get('variants')->getData() ?? [];
        $shipping = $this->form->get('shipping')->getData() ?? 5;
        foreach ($this->variants as $frameType => $qty) {
            $frameType = $frameType ?? Addons::FRAME_NONE;

            $cartItemQty = 0;

            if(isset($this->getCurrentEditItem()['data']['name']) && $frameType !== $this->getCurrentEditItem()['data']['name'] ?? Addons::FRAME_NONE) {
                $cartItemQty = self::getQuantityBySizes($frameType);
            }

            if ($qty > 0) {
                $this->totalQty += $qty;
                $framePrice = $this->getFramePrice($qty + $cartItemQty, $frameType);
                $this->subTotalAmount += number_format($framePrice * $qty, 2, '.', '');
                $this->data['items'][] = [
                    'name' => $frameType,
                    'quantity' => $qty,
                    'label' => $this->getVariantLabelName($frameType),
                    'unitAmount' => $framePrice
                ];
            }
        }

        $totalQuantity = $this->lightCartProvider->getTotalQuantity();
        $currentItemQuantity  = isset($this->getCurrentEditItem()['quantity']) ? $this->getCurrentEditItem()['quantity'] : 0;
        $this->updatedShipping = $this->orderWireStakeHelper->updateShipping(($this->totalQty + $totalQuantity) - $currentItemQuantity);
        $this->shipping = $this->orderWireStakeHelper->getShippingByDay($shipping, (($this->totalQty + $totalQuantity) - $currentItemQuantity), $this->subTotalAmount);
        $this->totalAmount = $this->subTotalAmount + $this->shipping['amount'] - $this->shipping['discountAmount'] - ($this->toggle ? $this->shipping['amount'] * $this->productConfigHelper->getDeliveryMethods()['REQUEST_PICKUP']['discount'] / 100 : $this->productConfigHelper->getDeliveryMethods()['DELIVERY']['discount'] / 100);

        $this->data['totalAmount'] = $this->totalAmount;
        $this->data['shipping'] = $this->shipping;
        $this->data['totalQty'] = $this->totalQty;
        $this->data['subTotalAmount'] = $this->subTotalAmount;
        $this->data['shippingCost'] = $this->toggle ? $this->shipping['amount'] * $this->productConfigHelper->getDeliveryMethods()['REQUEST_PICKUP']['discount'] / 100 : $this->shipping['amount'];

    }

    public function getSubtotalAmount(): float
    {
        return $this->subTotalAmount;
    }


    public function getFramePrice(int|string $quantity, string $frameType): float
    {
        $quantity = (int) $quantity;
        $currency = $this->store['currencyCode'] ?? 'USD';
        $framePricing = $this->orderWireStakeHelper->getFramePricing();

        $framePriceChart = $framePricing['frames']['pricing_' . $frameType]['pricing'];

        foreach ($framePriceChart as $framePrice) {
            if ($quantity >= $framePrice['qty']['from'] && $quantity <= $framePrice['qty']['to']) {
                return $framePrice[strtolower($currency)] ?? 0;
            }
        }
        return 0;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getFrameTypePrice(float $price, string $frameType): float|string
    {
        return Addons::getFrameTypePrice($price, $frameType);
    }

    public function getFrameQuantityType(string $frameType): string
    {
        return Addons::getFrameQuantityType($frameType);
    }

    public function getSubtotal(): float
    {
        $cartSubtotalAmount = $this->lightCartProvider->getTotalAmount();
        $currentItemAmount  = isset($this->getCurrentEditItem()['data']['totalAmount']) ? $this->getCurrentEditItem()['data']['totalAmount'] : 0;
        return ($this->subTotalAmount + $cartSubtotalAmount) - $currentItemAmount;
    }

    #[LiveAction]
    public function addToCart(): Response|FormView
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->calculateData();

        if(isset($this->product['isSelling']) && !$this->product['isSelling']) {
            $this->flashError = 'danger';
            $this->flashMessage = 'Frames are currently out of stock. Expected back in mid-August.';
            return $form->createView(); 
        }

        if ($this->totalQty < 1) {
            $form->get('variants')->addError(new FormError('Please add 1 or more to your quantity to meet the minimum required.'));
            $this->flashError = 'danger';
            $this->flashMessage = 'Please add 1 or more to your quantity to meet the minimum required.';
            return $form->createView();
        } else {
            $this->orderWireStakeHelper->addToCart([
                'editData' => $this->editData,
                'product' => $this->product,
                'variants' => $data['variants'],
                'comment' => $data['comment'],
                'shipping' => $this->shipping,
                'totalQty' => $this->totalQty,
                'subTotalAmount' => $this->subTotalAmount,
                'totalAmount' => $this->totalAmount,
                'toggle' => $this->toggle, 
                'isBlindShipping' => $this->isBlindShipping
            ]);

            return $this->redirectToRoute('cart');
        }
    }

}