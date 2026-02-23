<?php

namespace App\Component\Customer;

use App\Entity\Admin\Coupon;
use App\Entity\Cart;
use App\Form\ApplyCouponType;
use App\Service\CartManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "ApplyCouponForm",
    template: "components/_apply_coupon.html.twig"
)]
class ApplyCouponForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;

    public function __construct(private readonly EntityManagerInterface $entityManager, private CartManagerService $cartManagerService)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ApplyCouponType::class);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }
    public function getMaxDiscountedCoupons(): array
    {
        return $this->entityManager->getRepository(Coupon::class)->getMaxDiscountedCoupons();
    }

    #[LiveAction]
    public function applyCoupon()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->isSuccessful = true;
        try {
            $validateCoupon = $this->entityManager->getRepository(Coupon::class)->findOneBy(['code'=> $data['code'],'isEnabled' => true]);
            if (!$validateCoupon || ($validateCoupon->getEndDate() < new \DateTimeImmutable() || $validateCoupon->getUsesTotal() <= 0)) {
                $this->flashMessage = 'This coupon is incorrect or no longer valid.';
                $this->flashError = 'danger';
                return;
            }
            $couponName = $validateCoupon->getCouponName();
            $cart = $this->cartManagerService->getCart();
            $cartQuantity = $cart->getTotalQuantity();
            $minQty = $validateCoupon->getMinimumQuantity() ?? 0;
            $maxQty = $validateCoupon->getMaximumQuantity();

            $isQuantityEligible = $cartQuantity >= $minQty && ($maxQty === null || $cartQuantity <= $maxQty);
            if (!$isQuantityEligible) {
                $this->flashMessage = sprintf(
                    'To use the "%s" coupon code, the cart quantity must be at least %s ',
                    $couponName,
                    $minQty,
                    $maxQty ?? '∞'
                );
                $this->flashError = 'danger';
                return;
            }
           
            if($this->isMinQuantityNotMet($validateCoupon)){
                $this->flashMessage = 'The minimum cart value requirement for this coupon is not met. Minimum Cart value Should be: $'. $validateCoupon->getMinCartValue() ;
                $this->flashError = 'danger';
                return;
            }
            $cart = $this->cartManagerService->getCart();
            if ($cart->getCoupon() && strtolower($cart->getCoupon()->getCode()) === strtolower($data['code'])) {
                $this->flashMessage = 'This Coupon code is already applied.';
                $this->flashError = 'danger';
                return;
            }

            $cart->setCoupon($validateCoupon);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            $this->cartManagerService->updateCoupon($cart);
            $this->addFlash('success','Coupon has been successfully applied to your order.');
            return $this->redirectToRoute('cart', ['id' => $cart->getCartId()]);

        } catch (Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
        
    }

    private function isMinQuantityNotMet($validateCoupon): bool
    {
        return ($this->cartManagerService->getCart()->getSubTotal() < $validateCoupon->getMinCartValue());
    }
}