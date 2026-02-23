<?php

namespace App\Entity;

use App\Repository\OrderTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderTransactionRepository::class)]
class OrderTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(length: 255)]
    private ?string $transactionId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[ORM\Column(length: 50)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gatewayId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private array $metaData = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isPaymentLink = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $refundedAmount = null;

    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: OrderTransactionRefund::class, orphanRemoval: true)]
    private Collection $orderTransactionRefunds;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?AdminFile $proofFile = null;

    public function __construct()
    {
        $this->setTransactionId(uniqid());
        $this->setIsPaymentLink(false);
        $this->setRefundedAmount(0);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->metaData = [];
        $this->orderTransactionRefunds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
        return strtoupper($this->transactionId);
    }

    public function setTransactionId(string $transactionId): static
    {
        $this->transactionId = strtoupper($transactionId);

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getGatewayId(): ?string
    {
        return $this->gatewayId;
    }

    public function setGatewayId(?string $gatewayId): static
    {
        $this->gatewayId = $gatewayId;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    public function isIsPaymentLink(): ?bool
    {
        return $this->isPaymentLink;
    }

    public function setIsPaymentLink(bool $isPaymentLink): static
    {
        $this->isPaymentLink = $isPaymentLink;

        return $this;
    }

    public function getRefundedAmount(): ?string
    {
        return $this->refundedAmount;
    }

    public function setRefundedAmount(string $refundedAmount): static
    {
        $this->refundedAmount = $refundedAmount;

        return $this;
    }

    /**
     * @return Collection<int, OrderTransactionRefund>
     */
    public function getOrderTransactionRefunds(): Collection
    {
        return $this->orderTransactionRefunds;
    }

    public function addOrderTransactionRefund(OrderTransactionRefund $orderTransactionRefund): static
    {
        if (!$this->orderTransactionRefunds->contains($orderTransactionRefund)) {
            $this->orderTransactionRefunds->add($orderTransactionRefund);
            $orderTransactionRefund->setTransaction($this);
        }

        return $this;
    }

    public function removeOrderTransactionRefund(OrderTransactionRefund $orderTransactionRefund): static
    {
        if ($this->orderTransactionRefunds->removeElement($orderTransactionRefund)) {
            // set the owning side to null (unless already changed)
            if ($orderTransactionRefund->getTransaction() === $this) {
                $orderTransactionRefund->setTransaction(null);
            }
        }

        return $this;
    }

    public function getProofFile(): ?AdminFile
    {
        return $this->proofFile;
    }

    public function setProofFile(?AdminFile $proofFile): static
    {
        $this->proofFile = $proofFile;

        return $this;
    }

}
