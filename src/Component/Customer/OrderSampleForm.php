<?php

namespace App\Component\Customer;

use App\Constant\HomePageBlocks;
use App\Controller\Admin\Order\NewOrder\AddOns;
use App\Form\Page\OrderSampleType;
use App\Helper\OrderSampleHelper;
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
use App\Helper\ProductConfigHelper;
use App\Constant\Editor\Addons as EditorAddons;


#[AsLiveComponent(
    name: "OrderSampleForm",
    template: "components/order-sample/index.html.twig"
)]
class OrderSampleForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';

    #[LiveProp]
    public ?array $product;

    #[LiveProp]
    public ?array $editData;

    #[LiveProp(writable: true)]
    public bool $toggle = false;

    #[LiveProp(writable: true)]
    public bool $isBlindShipping = false;

    #[LiveProp(writable: true)]
    public bool $isFreeFreight = false;

    #[LiveProp(writable: true)]
    public bool $hasBiggerSize = false;

    #[LiveProp(writable: true)]
    public int $totalSampleQty = 0;

    private int $totalQty = 0;

    private float $subTotalAmount = 0;

    private float $totalAmount = 0;

    public array $shipping = [
        'day' => 5,
        'date' => '',
        'amount' => 0,
        'discount' => 0,
        'discountAmount' => 0,
    ];

    public array $data = [
        'items' => [],
        'totalQty' => 0,
        'subTotalAmount' => 0,
        'totalAmount' => 0,
        'shipping' => '',
        'shippingCost' => 0
    ];

    public array $addonsData = [];

    private bool $freeFreightToggled = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface        $mailer,
        private readonly RequestStack           $requestStack,
        private readonly HomePageBlocks         $homePageBlocks,
        private readonly OrderSampleHelper      $orderSampleHelper,
        private readonly LightCartProvider $lightCartProvider,
        private readonly ProductConfigHelper $productConfigHelper,
        private readonly AddOns $addOns,
        private readonly EditorAddons $editorAddOns
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrderSampleType::class, [], [
            'variants' => $this->product['variants'],
            'editData' => $this->editData,
        ]);
    }

    public function totalAmount(): float
    {
        return $this->totalAmount;
    }

    public function totalQuantity(): int
    {
        $this->calculateData();
        $this->getTotalQty();
        return $this->totalQty;
    }

    public function getCurrentEditItem(): array
    {
        return $this->editData ? end($this->editData['items']) ?? [] : [];
    }

    public function subTotal(): float
    {
        return $this->subTotalAmount;
    }

    public function getSubtotal(): float
    {
        $cartSubtotalAmount = $this->lightCartProvider->getTotalAmount();
        $currentItemAmount = isset($this->getCurrentEditItem()['data']['totalAmount']) ? $this->getCurrentEditItem()['data']['totalAmount'] : 0;
        return ($this->subTotalAmount + $cartSubtotalAmount) - $currentItemAmount;
    }

    public function getTotalQty()
    {
        $totalCartQty = 0;
        $cartItems = $this->lightCartProvider->getItems();
        $currentEditItemQty = isset($this->getCurrentEditItem()['quantity']) ? $this->getCurrentEditItem()['quantity'] : 0;
        foreach ($cartItems as $item) {
            $hasParentSku = isset($item['parentSku']) && $item['parentSku'] === 'SAMPLE';
            $hasCustomSizeParentSku = isset($item['data']['customSize']['parentSku']) && $item['data']['customSize']['parentSku'] === 'SAMPLE';
            if ($hasParentSku || $hasCustomSizeParentSku) {
                $totalCartQty += $item['quantity'];
            }
        }
        $this->totalSampleQty = $totalCartQty + $this->totalQty - $currentEditItemQty;
    }

    public function isBlindShipping(): bool
    {
        $this->isBlindShipping = $this->form->get('isBlindShipping')->getData() ?? false;
        return $this->isBlindShipping;
    }

    public function isFreeFreight(): bool
    {
        $isFreeFreight = $this->form->get('isFreeFreight')->getData() ?? false;
        $customSizes = $this->form->get('customSize')->getData() ?? [];
        $this->hasBiggerCustomSize($customSizes);

        if (!$this->freeFreightToggled) {
            $this->isFreeFreight = $this->hasBiggerSize ? true : $isFreeFreight;
        }
    
        return $this->isFreeFreight;
    }

    #[LiveAction]
    public function toggleFreeFreight(): void
    {
        $this->freeFreightToggled = true; 
        $this->isFreeFreight = !$this->isFreeFreight; 
    }

    public function toggle(): bool
    {
        $this->toggle = $this->form->get('pickup')->getData() ?? false;
        return $this->toggle;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getSubtotalAmount(): float
    {
        return $this->subTotalAmount;
    }

    private function calculateData(): void
    {
        $this->data['items'] = [];
        $this->totalQty = 0;
        $variants = $this->form->get('variants')->getData() ?? [];
        $customSizes = $this->form->get('customSize')->getData() ?? [];
        $sides = $this->form->get('chooseYourSides')->getData() ?? [];
        $shape = $this->form->get('chooseYourShape')->getData() ?? [];
        $totalAddonAmount = 0;

        if (!empty($customSizes)) {
            foreach ($customSizes as $customSize) {
                if($customSize['quantity'] > 0) {
                    $this->data['items'][] = [
                        'name' => ($customSize['width'] ?? 1) . 'x' . ($customSize['height'] ?? 1),
                        'quantity' => $customSize['quantity'] ?? 0,
                        'label' => 'CUSTOM-SIZE',
                        'addons' => [
                            'sides' => $sides === EditorAddons::SIDES_SINGLE ? $this->addOns::CONFIG['sides']['SINGLE'] : array_merge(
                                $this->addOns::CONFIG['sides']['DOUBLE'],
                                [
                                    'type' => 'FIXED',
                                    'amount' => $this->editorAddOns->getSidesData($sides)['amount'],
                                ]
                            ),
                            'shape' => $shape === EditorAddons::SHAPE_SQUARE ? $this->addOns::CONFIG['shape']['SQUARE'] : array_merge(
                                $this->addOns::CONFIG['shape']['CUSTOM'],
                                [
                                    'type' => 'FIXED',
                                    'amount' => $this->editorAddOns->getSampleShapesData($shape)['amount'],
                                ]
                            ),
                        ],
                        'unitAmount' => OrderSampleHelper::$yardSignPrice,
                        'totalAmount' => (OrderSampleHelper::$yardSignPrice + ($this->editorAddOns->getSidesData($sides)['amount'] ?? 0) + ($this->editorAddOns->getSampleShapesData($shape)['amount'] ?? 0)) * ($customSize['quantity'] ?? 0),
                    ];
                }

                if (!empty($customSize['quantity'])) {
                    $this->totalQty += $customSize['quantity'];
                }
            }
        }

        $shipping = $this->form->get('shipping')->getData() ?? 5;

        foreach ($variants as $name => $qty) {
            if ($qty) {
                $this->totalQty += $qty;
                $this->data['items'][] = [
                    'name' => $name,
                    'quantity' => $qty,
                    'label' => $name,
                    'addons' => [
                        'sides' => $sides === EditorAddons::SIDES_SINGLE ? $this->addOns::CONFIG['sides']['SINGLE'] : array_merge(
                            $this->addOns::CONFIG['sides']['DOUBLE'],
                            [
                                'type' => 'FIXED',
                                'amount' => $this->editorAddOns->getSidesData($sides)['amount'],
                            ]
                        ),
                        'shape' => $shape === EditorAddons::SHAPE_SQUARE ? $this->addOns::CONFIG['shape']['SQUARE'] : array_merge(
                            $this->addOns::CONFIG['shape']['CUSTOM'],
                            [
                                'type' => 'FIXED',
                                'amount' => $this->editorAddOns->getSampleShapesData($shape)['amount'],
                            ]
                        ),
                    ],
                    'unitAmount' => OrderSampleHelper::$yardSignPrice,
                    'totalAmount' => (OrderSampleHelper::$yardSignPrice + ($this->editorAddOns->getSidesData($sides)['amount'] ?? 0) + $this->editorAddOns->getSampleShapesData($shape)['amount'] ?? 0) * $qty,
                ];
            }
        }

        $this->addonsData = [
            'sides' => $sides === EditorAddons::SIDES_SINGLE ? $this->addOns::CONFIG['sides']['SINGLE'] : array_merge(
                $this->addOns::CONFIG['sides']['DOUBLE'],
                [
                    'type' => 'FIXED',
                    'amount' => $this->editorAddOns->getSidesData($sides)['amount'],
                ]
            ),
            'shape' => $shape === EditorAddons::SHAPE_SQUARE ? $this->addOns::CONFIG['shape']['SQUARE'] : array_merge(
                $this->addOns::CONFIG['shape']['CUSTOM'],
                [
                    'type' => 'FIXED',
                    'amount' => $this->editorAddOns->getSampleShapesData($shape)['amount'],
                ]
            ),
            'frame' => $this->addOns::CONFIG['frame']['NONE']
        ];

        foreach ($this->addonsData as $key => $value) {
            $totalAddonAmount += $value['amount'];
        }

        $this->shipping = $this->orderSampleHelper->getShippingByDay($shipping);

        $this->subTotalAmount = (OrderSampleHelper::$yardSignPrice + $totalAddonAmount) * $this->totalQty;
        $this->totalAmount = $this->subTotalAmount + $this->shipping['amount'];

        $this->totalAmount = $this->subTotalAmount + $this->shipping['amount'] - $this->shipping['discountAmount'] - ($this->toggle ? $this->shipping['amount'] * $this->productConfigHelper->getDeliveryMethods()['REQUEST_PICKUP']['discount'] / 100 : $this->productConfigHelper->getDeliveryMethods()['DELIVERY']['discount'] / 100);
        $this->data['shipping'] = $this->shipping;
        $this->data['totalQty'] = $this->totalQty;
        $this->data['subTotalAmount'] = $this->subTotalAmount;
        $this->data['shippingCost'] = $this->toggle ? $this->shipping['amount'] * $this->productConfigHelper->getDeliveryMethods()['REQUEST_PICKUP']['discount'] / 100 : $this->shipping['amount'];
    }

    #[LiveAction]
    public function addToCart(): Response|FormView
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->calculateData();
        $this->getTotalQty();

        if ($this->totalQty < 1) {
            $form->get('variants')->addError(new FormError('Please add 1 or more to your quantity to meet the minimum required.'));
            $this->flashError = 'danger';
            $this->flashMessage = 'Please add 1 or more to your quantity to meet the minimum required.';
            return $form->createView();
        } else if ($this->totalSampleQty > 20) {
            $form->get('variants')->addError(new FormError('Maximum order quantity of 20 samples per order. Please reduce your total quantity below 20.'));
            $this->flashError = 'danger';
            $this->flashMessage = 'Maximum order quantity of 20 samples per order. Please reduce your total quantity below 20.';
            return $form->createView();
        } else {
            $this->orderSampleHelper->addToCart([
                'editData' => $this->editData,
                'product' => $this->product,
                'variants' => $data['variants'],
                'customSize' => $data['customSize'],
                'comment' => $data['comment'],
                'shipping' => $this->shipping,
                'totalQty' => $this->totalQty,
                'subTotalAmount' => $this->subTotalAmount,
                'totalAmount' => $this->totalAmount,
                'toggle' => $this->toggle,
                'data' => $this->data,
                'addons' => $this->addonsData,
                'isBlindShipping' => $this->isBlindShipping,
                'isFreeFreight' => $this->isFreeFreight
            ]);

            return $this->redirectToRoute('cart');
        }
    }

    private function hasBiggerCustomSize(array $customSizes): void
    {
        $this->hasBiggerSize = false;

        foreach ($customSizes as $customSize) {
            $width = $customSize['width'] ?? null;
            $height = $customSize['height'] ?? null;

            if (!is_numeric($width) || !is_numeric($height)) {
                continue;
            }

            if ($this->isBiggerSize((int) $width, (int) $height)) {
                $this->hasBiggerSize = true;
                return;
            }
        }
    }

    private function isBiggerSize(int $width, int $height, int $refWidth = 48, int $refHeight = 24): bool {
        $fitsInOrientation1 = $width <= $refWidth && $height <= $refHeight;
        $fitsInOrientation2 = $width <= $refHeight && $height <= $refWidth;
    
        return !($fitsInOrientation1 || $fitsInOrientation2);
    }
}