<?php

namespace App\Controller\Admin\Component\Order\History;

use App\Form\Admin\Referral\ReferralType;
use App\Repository\ReferralRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;

#[AsLiveComponent(
    name: "OrderHistoryReferralsForm",
    template: "admin/components/order/history/order_history_referrals_form.html.twig"
)]
class OrderHistoryReferralsForm extends AbstractController
{
    
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;
    
    public function __construct(
        private readonly ReferralRepository $referralRepository,
    ) {
    }

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;

    public ?\DateTime $startDate = null;

    public ?\DateTime $endDate = null;

    #[LiveProp]
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ReferralType::class);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    public function getStartDate(): \DateTime
    {
        return $this->getForm()->get('startDate')->getData() ?? (new \DateTime())->modify('-1 days');
    }

    public function getEndDate(): \DateTime
    {
        return $this->getForm()->get('endDate')->getData() ?? (new \DateTime());
    } 

    public function getReferrals(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $this->startDate = $startDate ?? $this->getForm()->get('startDate')->getData() ?? (new \DateTime())->modify('-1 days');
        $this->endDate = $endDate ?? $this->getForm()->get('endDate')->getData() ?? (new \DateTime());

        $startDate = new \DateTimeImmutable(($this->startDate)->format('Y-m-d H:i:s'));
        $endDate = new \DateTimeImmutable(($this->endDate)->format('Y-m-d H:i:s'));

        $referrals = $this->referralRepository->getReferredUsers(
            $startDate, 
            $endDate
        );

        $result = [];
        $totalRequestedCoupons = 0;
        $totalUsedCoupons = 0;
        $totalDiscountGiven = 0.0;
    
        foreach ($referrals as $referral) {
            $coupon = $referral->getCoupon();
            $orders = $coupon ? $coupon->getOrders()->first() : null;
    
            $isCouponUsed = $coupon && $coupon->getUsesTotal() == 0;
    
            $totalRequestedCoupons++;
            if ($isCouponUsed) {
                $totalUsedCoupons++;
                $totalDiscountGiven += $orders ? $orders->getCouponDiscountAmount() : 0;
            }
    
            $result[] = [
                'referrerName' => $referral->getReferrer() ? $referral->getReferrer()->getName() : '',
                'referralDate' => $referral->getCreatedAt() ? $referral->getCreatedAt()->format('Y-m-d H:i:s') : '',
                'referredPerson' => $referral->getReferred() ? $referral->getReferred()->getEmail() : '',
                'isCouponUsed' => $isCouponUsed ? 'Used' : 'Unused',
                'couponDiscountAmount' => $orders ? $orders->getCouponDiscountAmount() : 0, 
            ];
        }
       
        return [
            'referrals' => $result,
            'totals' => [
                'requestedCoupons' => $totalRequestedCoupons,
                'usedCoupons' => $totalUsedCoupons,
                'discountGiven' => $totalDiscountGiven,
            ],
        ];        
    }

    #[LiveAction]
    public function search(): void
    {
        $this->startDate = $this->getForm()->get('startDate')->getData();
        $this->endDate = $this->getForm()->get('endDate')->getData();

        try {

        } catch (\Exception $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashError = 'danger';
        }
    }

}