<?php

namespace App\Component\Customer\Cart;

use App\Entity\CartItem;
use App\Form\CartItemQuantityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "CartItemQuantityForm",
    template: "components/cart/_item_quantity.html.twig"
)]
class CartItemQuantityForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    public CartItem $cartItem;

    #[LiveProp(writable: false)]
    public ?array $storeInfo = null;

    public function __construct(
        private readonly EntityManagerInterface     $entityManager,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CartItemQuantityType::class, $this->cartItem);
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $this->form->get('quantity')->getData();
            $totalSampleQty = 0;
            foreach($this->cartItem->getCart()->getCartItems() as $cartItem) {
                if($cartItem->getProduct()->getParent()->getSku() === 'SAMPLE' || $cartItem->getDataKey('isCustomSize') && $cartItem->getDataKey('isSample')) {
                    $totalSampleQty += $cartItem->getQuantity();
                }
            }
            if($totalSampleQty > 20) {
                $this->addFlash('warning', 'Maximum order quantity of 20 samples per order. Please reduce your total quantity below 20.');
                return $this->redirectToRoute('cart');
            }
            if($this->cartItem->getProduct()->getParent()->getSku() === 'SAMPLE' || $this->cartItem->getDataKey('isCustomSize') && $this->cartItem->getDataKey('isSample')){
                if ($quantity > 3) {
                    $quantity = 3;
                }
            }
            $this->cartItem->setQuantity($quantity);

            $addons = $this->cartItem->getDataKey('addons');

            if ($this->cartItem->getProduct()->getParent()->getProductType()->getSlug() === 'yard-letters') {
                $productMetaData = $this->cartItem->getProduct()->getParent()->getProductMetaData();
                $frameQuantities = $productMetaData['frameTypes'] ?? null;
                if ($frameQuantities) {
                    foreach ($frameQuantities as $key => $frameData) {
                        if (isset($addons['frame'][$key])) {
                            $frame = $addons['frame'][$key];
                            $frame['quantity'] = $frameData;
                            $addons['frame'][$key] = $frame;
                        }
                    }
                    foreach ($addons as $key => $addon) {
                        if (isset($addon['key']) && $key !== 'frame') {
                            $addon['quantity'] = $quantity;
                            $addons[$key] = $addon;
                        }
                    }
                } else {
                    $productImages = $this->cartItem->getProduct()->getParent()->getProductImages();
                    $frameQuantityFallback = count($productImages);
                    foreach ($addons['frame'] as $key => $frame) {
                        $frame = $addons['frame'][$key];
                        $frame['quantity'] = $frameQuantityFallback;
                        $addons['frame'][$key] = $frame;
                    }
                    foreach ($addons as $key => $addon) {
                        if (isset($addon['key']) && $key !== 'frame') {
                            $addon['quantity'] = $quantity;
                            $addons[$key] = $addon;
                        }
                    }
                }
            } else {
                foreach ($addons as $key => $addon) {
                    $addon['quantity'] = $quantity;
                    $addons[$key] = $addon;
                }
            }
            $this->cartItem->setDataKey('addons', $addons);
            $this->cartItem->setDataKey('quantity', $quantity);
            $this->entityManager->persist($this->cartItem);
            $this->entityManager->flush();
            $this->addFlash('success', 'Quantity Updated Successfully');
            return $this->redirectToRoute('cart');
        }
    }
}
