<?php

namespace App\Entity\Reward;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Repository\Reward\RewardTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RewardTransactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RewardTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $transactionId = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $actionType = null;

    #[ORM\Column]
    private ?float $points = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $comment = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'rewardTransactions')]
    private ?Order $order = null;

    #[ORM\ManyToOne(inversedBy: 'rewardTransactions')]
    private ?Reward $reward = null;

    #[ORM\Column]
    private ?float $pointsValue = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $metaData = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;


    const STATUS_PENDING = 'PENDING'; // Order submitted
    const STATUS_COMPLETED = 'COMPLETED'; // Order delivered
    const STATUS_CANCELED = 'CANCELLED'; // Order not delivered
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_REFUNDED = 'REFUNDED';

    const CREDIT = 'CREDIT';
    const DEBIT = 'DEBIT';

    const TRANSACTION_STATUS = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELED,
        self::STATUS_EXPIRED,
        self::STATUS_REFUNDED
    ];

    const TRANSACTION_TYPES = [
        self::CREDIT,
        self::DEBIT,
    ];

    const DEFAULT_POINTS_VALUE = 1.0;

    const DISCOUNT_TYPE_PERCENT = 'PERCENT';
    const DISCOUNT_TYPE_FIXED = 'FIXED';

    const DISCOUNT_TYPES = [
        self::DISCOUNT_TYPE_PERCENT,
        self::DISCOUNT_TYPE_FIXED,
    ];

    const DEFAULT_DISCOUNT_VALUE = 5.0;
    const DEFAULT_MAXIMUM_DISCOUNT = 50.0;

    const ORDER_CREDITS_REWARD = 'ORDER_CREDITS_REWARD';
    const ORDER_CREDITS_REFERRAL_REWARD = 'ORDER_CREDITS_REFERRAL_REWARD';
    const ORDER_PLACED_SPEND_REWARDS = 'ORDER_PLACED_SPEND_REWARDS';
    const ORDER_REFUNDED_REWARDS = 'ORDER_REFUNDED_REWARDS';
    const ADMIN_CREDIT_CUSTOMER_REWARDS = 'ADMIN_CREDIT_CUSTOMER_REWARDS';
    const ADMIN_DEBIT_CUSTOMER_REWARDS = 'ADMIN_DEBIT_CUSTOMER_REWARDS';

    const REWARD_ACTION_LABELS = [
        self::ORDER_CREDITS_REWARD => 'Reward credited for order placement',
        self::ORDER_CREDITS_REFERRAL_REWARD => 'Reward credited for referral',
        self::ORDER_PLACED_SPEND_REWARDS => 'Reward used for order placement',
        self::ORDER_REFUNDED_REWARDS => 'Reward refunded due to order cancellation',
        self::ADMIN_CREDIT_CUSTOMER_REWARDS => 'Rewards credited by admin for customer',
        self::ADMIN_DEBIT_CUSTOMER_REWARDS => 'Rewards deducted by admin for customer',
    ];


    public function __construct()
    {
        $this->setTransactionId(uniqid());
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->pointsValue = self::DEFAULT_POINTS_VALUE;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReward(): ?Reward
    {
        return $this->reward;
    }

    public function setReward(?Reward $reward): static
    {
        $this->reward = $reward;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::CREDIT, self::DEBIT])) {
            throw new \InvalidArgumentException("Invalid transaction type");
        }
        $this->type = $type;

        return $this;
    }

    public function getPoints(): ?float
    {
        return $this->points;
    }

    public function setPoints(float $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getStatus(): ?string
    {
        $order = $this->getOrder();

        if ($order) {
            $orderStatus = $order->getStatus();
            $isDebit = $this->type === self::DEBIT;

            switch ($orderStatus) {
                case OrderStatusEnum::CANCELLED:
                    $this->status = $isDebit ? self::STATUS_REFUNDED : self::STATUS_CANCELED;
                    break;
                case OrderStatusEnum::COMPLETED:
                case OrderStatusEnum::SHIPPED:
                    $this->status = self::STATUS_COMPLETED;
                    break;
            }
        }

        return $this->status;
    }


    public function setStatus(string $status): static
    {
        if (!in_array($status, self::TRANSACTION_STATUS)) {
            throw new \InvalidArgumentException("Invalid status");
        }
        
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): static
    {
        $this->transactionId = strtoupper($transactionId);

        return $this;
    }

    public function getPointsValue(): ?float
    {
        return $this->pointsValue;
    }

    public function setPointsValue(float $pointsValue): static
    {
        $this->pointsValue = $pointsValue;

        return $this;
    }

    /**
     * Convert transaction amount to points based on pointsValue.
     */
    public function convertAmountToPoints(float $amount): float
    {
        if ($this->pointsValue === null) {
            throw new \RuntimeException('Points value is not set.');
        }
        return $amount / $this->pointsValue;
    }

    /**
     * Convert points to transaction amount based on pointsValue.
     */
    public function convertPointsToAmount(float $points): float
    {
        if ($this->pointsValue === null) {
            throw new \RuntimeException('Points value is not set.');
        }
        return $points * $this->pointsValue;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function setMetaData(?array $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function getMetaDataKey(string $key)
    {
        return $this->metaData[$key] ?? null;
    }

    public function setMetaDataKey(string $key, $value): static
    {
        $metaData = $this->metaData;
        $metaData[$key] = $value;
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * Get the expiration date which is 6 months after the created date.
     * This applies only to credit transactions.
     */
    public function getExpireAt(): ?\DateTimeImmutable
    {
        if ($this->createdAt === null || $this->type !== self::CREDIT) {
            return null;
        }

        return $this->createdAt->add(new \DateInterval('P6M'));
    }

    /**
     * Check if the transaction is expired.
     * This applies only to credit transactions.
     */
    public function getIsExpired(): bool
    {
        if ($this->type !== self::CREDIT) {
            return false; // Debit transactions never expire
        }

        return $this->getExpireAt() < new \DateTimeImmutable() || $this->getStatus() === self::STATUS_EXPIRED;
    }

    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        if (!array_key_exists($actionType, self::REWARD_ACTION_LABELS)) {
            throw new \InvalidArgumentException("Invalid reward action type");
        }
        $this->actionType = $actionType;

        return $this;
    }

    /**
     * Add an activity log to the metaData.
     */
    public function addActivityLog(array $activity): static
    {
        if (!isset($this->metaData['activity'])) {
            $this->metaData['activity'] = [];
        }
        $this->metaData['activity'][] = $activity;
        return $this;
    }

    /**
     * Get all activity logs from the metaData.
     */
    public function getActivityLogs(): array
    {
        return $this->metaData['activity'] ?? [];
    }

    public function getActionTypeLabel(string $type): string
    {
        return RewardTransaction::REWARD_ACTION_LABELS[$type];
    }
    
}
