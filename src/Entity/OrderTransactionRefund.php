<?php

namespace App\Entity;

use App\Repository\OrderTransactionRefundRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderTransactionRefundRepository::class)]
class OrderTransactionRefund
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderTransactionRefunds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrderTransaction $transaction = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\ManyToOne]
    private ?User $refundedBy = null;

    #[ORM\Column(type: Types::JSON)]
    private array $metaData = [];

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $refundType = null;

    public function __construct()
    {
        $this->setStatus('PENDING');
        $this->setRefundType('FULL_REFUND');
        $this->setCreatedAt(new \DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransaction(): ?OrderTransaction
    {
        return $this->transaction;
    }

    public function setTransaction(?OrderTransaction $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getRefundedBy(): ?User
    {
        return $this->refundedBy;
    }

    public function setRefundedBy(?User $refundedBy): static
    {
        $this->refundedBy = $refundedBy;

        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function setMetaData(array $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function getMetaDataKey(string $key)
    {
        if (isset($this->metaData[$key])) {
            return $this->metaData[$key];
        }

        return null;
    }

    public function setMetaDataKey(string $key, $value): self
    {
        $metaData = $this->metaData;
        $metaData[$key] = $value;
        $this->metaData = $metaData;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
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

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(\DateTimeImmutable $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }

    public function getRefundType(): ?string
    {
        return $this->refundType;
    }

    public function setRefundType(string $refundType): static
    {
        $this->refundType = $refundType;

        return $this;
    }
}
