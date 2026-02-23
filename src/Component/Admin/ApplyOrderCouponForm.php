<?php

namespace App\Component\Admin;

use App\Entity\Admin\Coupon;
use App\Entity\Order;
use App\Form\Admin\Order\ApplyOrderCouponType;
use App\Service\OrderLogger;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent(
    name: "ApplyOrderCouponForm",
    template: "admin/components/apply-order-coupon.html.twig"
)]
class ApplyOrderCouponForm extends AbstractController
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

    #[LiveProp(fieldName: 'formData')]
    public ?Order $order;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderService           $orderService,
        private readonly OrderLogger            $orderLogger,
    ){}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ApplyOrderCouponType::class);
    }
    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();
        $this->isSuccessful = true;
        $this->validate();
        $order = $this->order;
        try {
            $validateCoupon = $this->entityManager->getRepository(Coupon::class)->findOneBy(['code'=> $data['code'],'isEnabled' => true]);
            if (empty($validateCoupon) || !$validateCoupon && $validateCoupon->getEndDate() > new \DateTime('now') || $validateCoupon->getUsesTotal() <= 0) {
                $this->flashMessage = 'This coupon is incorrect or no longer valid.';
                $this->flashError = 'danger';
                $this->resetValidation();
                return;
            }

            if ($order->getCoupon() && strtolower($order->getCoupon()->getCode()) === strtolower($data['code'])) {
                $this->flashMessage = 'This Coupon code is already applied.';
                $this->flashError = 'danger';
                $this->resetValidation();
                return;
            }

            $couponAmount = 0;

            if ($validateCoupon instanceof Coupon) {
                $totalAmount = $order->getSubTotalAmount();
                $couponAmount = $this->orderService->calculateCouponAmount($validateCoupon, $totalAmount);
                if ($couponAmount > $totalAmount) {
                    $this->flashMessage = 'Coupon amount cannot be greater than the Subtotal amount.';
                    $this->flashError = 'danger';
                    $this->resetValidation();
                    return;
                }
            }

            $order->setCouponDiscountAmount($couponAmount);
            if($order->getTotalAmount() != 0) {
                $order->setTotalAmount($order->getTotalAmount() - $couponAmount);
            }
            $order->setCoupon($validateCoupon);

            $order = $this->orderService->updatePaymentStatus($order);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $this->addFlash('success','Coupon has been successfully applied to your order.');
            $this->orderLogger->setOrder($order);
            $this->orderLogger->log('Successfully applied coupon "' . $validateCoupon->getCode() . '" with (discount: $' . number_format($couponAmount, 2) . ') to order ID ' . $order->getOrderId());
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);

        } catch (\Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
        
    }

}