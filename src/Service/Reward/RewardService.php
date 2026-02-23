<?php

namespace App\Service\Reward;

use App\Entity\Reward\Reward;
use App\Entity\Reward\RewardTransaction;
use App\Entity\Order;
use App\Entity\AppUser;
use App\Entity\AdminUser;
use App\Entity\Cart;
use App\Repository\Reward\RewardTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RewardService extends AbstractController
{

    protected ?Order $order;
    protected ?Cart $cart;
    protected AppUser|AdminUser $user;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RewardTransactionRepository $rewardTransactionRepository
    ){}

    public function updateRewardPoints(
        Reward $reward,
        float $points,
        string $comment,
        string $type,
        AppUser|AdminUser $user,
        string $status = RewardTransaction::STATUS_COMPLETED,
        array $metaData = [],
        ?string $actionType = null,
        ?Order $order = null
    ): void {
        $this->order = $order;
        $this->user = $user;
        $transaction = $this->createTransaction($points, $comment, $status, $type, $metaData, $actionType, $order);
        $this->updateRewardAndFlush($reward, $transaction);
    }

    public function bulkUpdateRewardPoints(Reward $reward, array $transactions, string $type, array $metaData, string $actionType): void
    {
        foreach ($transactions as $data) {
            $order = $data['order'] ?? null; // Assuming each transaction data has an order
            $points = $data['points'];
            $comment = $data['comment'];

            $transaction = $this->createTransaction($points, $comment, RewardTransaction::STATUS_COMPLETED, $type, $metaData, $actionType, $order);
            $this->updateRewardAndFlush($reward, $transaction);
        }
    }

    public function getTransactionHistory(Reward $reward): Collection
    {
        return $reward->getRewardTransactions();
    }

    public function calculateAvailablePoints(Reward $reward): float|string
    {
        $reward->recalculatePoints();
        return number_format($reward->getAvailablePoints(), 2, '.', '');
    }

    public function validateTransaction(float $points, Reward $reward): bool
    {
        return $points <= $this->calculateAvailablePoints($reward);
    }

    private function createTransaction(float $points, string $comment, string $status, string $type, array $metaData = [], ?string $actionType = null, ?Order $order = null): RewardTransaction
    {

        $transaction = new RewardTransaction();
        $transaction->setType($type)
                    ->setPoints($points)
                    ->setComment($comment)
                    ->setStatus($status)
                    ->setActionType($actionType ?: self::getActionType($order, $type))
                    ->setMetaData($metaData)
                    ->setOrder($order);
        $transaction->addActivityLog(self::generateTransactionLog($order));

        $this->entityManager->persist($transaction);
        return $transaction;
    }

    private function getActionType(?Order $order, string $type): string
    {
        if ($order && $type === RewardTransaction::CREDIT) {
            return RewardTransaction::ORDER_CREDITS_REWARD;
        } elseif ($order && $type === RewardTransaction::DEBIT) {
            return RewardTransaction::ORDER_PLACED_SPEND_REWARDS;
        } elseif ($type === RewardTransaction::CREDIT) {
            return RewardTransaction::ADMIN_CREDIT_CUSTOMER_REWARDS;
        } elseif ($type === RewardTransaction::DEBIT) {
            return RewardTransaction::ADMIN_DEBIT_CUSTOMER_REWARDS;
        }

        return $type;
    }

    public function getActionTypeLabel(string $type): string
    {
        return RewardTransaction::REWARD_ACTION_LABELS[$type];
    }

    public function generateTransactionLog(?Order $order = null): array
    {
        if(!$order) {
            $currentUser = self::getCurrentUser();
        }else{
            $currentUser = $order->getUser();
        }

        if($currentUser instanceof AdminUser) {            
            $activity = [
                'name' => $currentUser->getName(),
                'email' => $currentUser->getEmail(),
                'performedBy' => $currentUser->getName() . ' (' . $currentUser->getEmail() . ')',
            ];
        }elseif($currentUser instanceof AppUser) {
            $activity = [
                'name' => $currentUser->getName(),
                'email' => $currentUser->getEmail(),
            ];
        }

        return $activity;
    }

    private function updateRewardAndFlush(Reward $reward, RewardTransaction $transaction): void
    {
        $reward->addRewardTransaction($transaction);
        $this->entityManager->flush();
    }

    public function getOrCreateReward(AppUser $user): Reward
    {
        if (!$user->getReward()) {
            $reward = new Reward();
            $reward->setUser($user);
            $reward->setTotalPoints(0);
            $reward->setUsedPoints(0);
            $reward->setPendingPoints(0);
            $reward->setAvailablePoints(0);
            $reward->setCreatedAt(new \DateTimeImmutable());
            $reward->setUpdatedAt(new \DateTimeImmutable());
            $reward->recalculatePoints();
            $user->setReward($reward);

            $this->entityManager->persist($user);
            $this->entityManager->persist($reward);
            $this->entityManager->flush();
        } else {
            $user->getReward()->recalculatePoints();
        }

        return $user->getReward();
    }

    public function isUserLoggedIn(): bool
    {
        return $this->getUser() ? true : false;
    }

    public function getCurrentUser(): AppUser|AdminUser|null
    {
        return $this->getUser();
    }

    public static function calculateCartReward(
        Cart $cart,
        ?string $discountType = RewardTransaction::DISCOUNT_TYPE_PERCENT,
        ?float $discountValue = RewardTransaction::DEFAULT_DISCOUNT_VALUE
    ): float|string
    {
        $subtotal = $cart->getSubtotal();

        if ($discountType === RewardTransaction::DISCOUNT_TYPE_PERCENT) {
            $discount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === RewardTransaction::DISCOUNT_TYPE_FIXED) {
            $discount = min($subtotal, $discountValue); // Ensure the fixed discount does not exceed the subtotal
        } else {
            throw new \InvalidArgumentException('Invalid discount type');
        }

        $discount = min($discount, RewardTransaction::DEFAULT_MAXIMUM_DISCOUNT);

        return (float)number_format($discount, 2, '.', '');
    }

    public function calculateCartDiscount(Cart $cart): float
    {
        if (!$cart) {
            return 0;
        }

        $user = self::getCurrentUser();

        if (!$user || !$user->getReward()) {
            return 0;
        }

        $availablePoints = $user->getReward()->getAvailablePoints();
        $logoDiscountAmount = $cart->getAdditionalDiscount()['YSPLogoDiscount']['amount'] ?? 0.0;
        $shippingDiscount = $cart->getAdditionalDiscount()['shippingDiscount']['amount'] ?? 0.0;
        $prePackedDiscountAmount = $cart->getAdditionalDiscount()['prePackedDiscount']['amount'] ?? 0.0;
        $total = $cart->getSubTotal() - $logoDiscountAmount - $shippingDiscount - $prePackedDiscountAmount;
        $subtotal = ($total  - $cart->getCouponAmount());
        $discount = min($subtotal, $availablePoints, RewardTransaction::DEFAULT_MAXIMUM_DISCOUNT);

        return (float)number_format($discount, 2, '.', '');
    }

    public function updateOrderRewardPoints(Order $order, ?AppUser $user = null): void
    {
        $this->user = $order->getUser();

        if($user instanceof AppUser){
            $this->user = $user;
        }

        self::getOrCreateReward($this->user);

        $orderPlacementCredit = RewardTransaction::REWARD_ACTION_LABELS[RewardTransaction::ORDER_CREDITS_REWARD];
        $orderPlacementDebit = RewardTransaction::REWARD_ACTION_LABELS[RewardTransaction::ORDER_PLACED_SPEND_REWARDS];

        $cartReward = self::calculateCartReward($order->getCart());
        $cartDiscount = self::calculateCartDiscount($order->getCart());

        if($cartReward > 0) {
            self::updateRewardPoints(
                reward: $this->user->getReward(),
                points: $cartReward,
                comment: $orderPlacementCredit,
                type: RewardTransaction::CREDIT,
                user: $this->user,
                status: RewardTransaction::STATUS_PENDING,
                order: $order
            );
        }

        if($cartDiscount > 0 && self::isUserLoggedIn()) {
            self::updateRewardPoints(
                reward: $this->user->getReward(),
                points: $cartDiscount,
                comment: $orderPlacementDebit,
                type: RewardTransaction::DEBIT,
                user: $this->user,
                status: RewardTransaction::STATUS_COMPLETED,
                order: $order
            );
        }
    }

    public function getBadgeClass(string $status): string
    {
        $badgeClasses = [
            RewardTransaction::STATUS_PENDING => 'bg-warning',
            RewardTransaction::STATUS_COMPLETED => 'bg-success',
            RewardTransaction::STATUS_CANCELED => 'bg-danger',
            RewardTransaction::STATUS_EXPIRED => 'bg-secondary',
            RewardTransaction::STATUS_REFUNDED => 'bg-info',
        ];

        return $badgeClasses[$status] ?? 'bg-primary';
    }

    public function getLatestTransactionExpiry(Reward $reward): ?\DateTimeImmutable
    {
        $reward->recalculatePoints();

        $completedCreditTransactions = $reward->getRewardTransactions()->filter(function (RewardTransaction $transaction) {
            return $transaction->getStatus() === RewardTransaction::STATUS_COMPLETED && $transaction->getType() === RewardTransaction::CREDIT;
        });

        return $completedCreditTransactions->isEmpty() ? null : $completedCreditTransactions->last()->getExpireAt();
    }

    public function getOldestTransactionExpiry(Reward $reward): ?\DateTimeImmutable
    {
        $completedCreditTransactions = $reward->getRewardTransactions()->filter(function (RewardTransaction $transaction) {
            return $transaction->getStatus() === RewardTransaction::STATUS_COMPLETED && $transaction->getType() === RewardTransaction::CREDIT;
        });

        return $completedCreditTransactions->isEmpty() ? null : $completedCreditTransactions->first()->getExpireAt();
    }

    public function getExpireDate(Reward $reward): ?string
    {
        $latestTransactionExpiry = $this->getLatestTransactionExpiry($reward);
        return $latestTransactionExpiry->format('M d, Y');
    }

}
