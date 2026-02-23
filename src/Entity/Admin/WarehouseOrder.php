<?php

namespace App\Entity\Admin;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Repository\Admin\WarehouseOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order as OrderCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\Criteria;


#[ORM\Entity(repositoryClass: WarehouseOrderRepository::class)]
class WarehouseOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'warehouseOrder', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['apiData'])]
    private ?Order $order = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $printerName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['apiData'])]
    private ?string $printed = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['apiData'])]
    private ?\DateTimeInterface $shipBy = null;

    #[ORM\Column(length: 50)]
    #[Groups(['apiData'])]
    private ?string $printStatus = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: WarehouseOrderLog::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $warehouseOrderLogs;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $driveLink = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $comments = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $shippingService = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'orderGroup')]
    #[Groups(['apiData'])]
    private ?WarehouseOrderGroup $warehouseOrderGroup = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?bool $isProofPrinted = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['apiData'])]
    private ?int $sortIndex = null;

    #[ORM\ManyToOne]
    #[Groups(['apiData'])]
    private ?User $proofPrintedBy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['apiData'])]
    private ?\DateTimeImmutable $proofPrintedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->printStatus = WarehouseOrderStatusEnum::READY;
        $this->warehouseOrderLogs = new ArrayCollection();
        $this->isProofPrinted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getPrinterName(): ?string
    {
        return $this->printerName;
    }

    public function setPrinterName(?string $printerName): static
    {
        $this->printerName = $printerName;

        return $this;
    }

    public function getPrinted(): ?string
    {
        return $this->printed;
    }

    public function setPrinted(?string $printed): static
    {
        $this->printed = $printed;

        return $this;
    }

    public function getShipBy(): ?\DateTimeInterface
    {
        return $this->shipBy;
    }

    public function setShipBy(?\DateTimeInterface $shipBy): static
    {
        $this->shipBy = $shipBy;

        return $this;
    }

    public function getPrintStatus(): ?string
    {
        return $this->printStatus;
    }

    public function setPrintStatus(string $printStatus): static
    {
        $this->printStatus = $printStatus;

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

    /**
     * @return Collection<int, WarehouseOrderLog>
     */
    public function getWarehouseOrderLogs(): Collection
    {
        $criteria = Criteria::create()->orderBy(['createdAt' => OrderCollection::Descending]);

        return $this->warehouseOrderLogs->matching($criteria);
    }

    public function addWarehouseOrderLog(WarehouseOrderLog $warehouseOrderLog): static
    {
        if (!$this->warehouseOrderLogs->contains($warehouseOrderLog)) {
            $this->warehouseOrderLogs->add($warehouseOrderLog);
            $warehouseOrderLog->setOrder($this);
        }

        return $this;
    }

    public function removeWarehouseOrderLog(WarehouseOrderLog $warehouseOrderLog): static
    {
        if ($this->warehouseOrderLogs->removeElement($warehouseOrderLog)) {
            // set the owning side to null (unless already changed)
            if ($warehouseOrderLog->getOrder() === $this) {
                $warehouseOrderLog->setOrder(null);
            }
        }

        return $this;
    }

    public function getDriveLink(): ?string
    {
        return $this->driveLink;
    }

    public function setDriveLink(?string $driveLink): static
    {
        $this->driveLink = $driveLink;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getShippingService(): ?string
    {
        return $this->shippingService;
    }

    public function setShippingService(?string $shippingService): static
    {
        $this->shippingService = $shippingService;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getWarehouseOrderGroup(): ?WarehouseOrderGroup
    {
        return $this->warehouseOrderGroup;
    }

    public function setWarehouseOrderGroup(?WarehouseOrderGroup $warehouseOrderGroup): static
    {
        $this->warehouseOrderGroup = $warehouseOrderGroup;

        return $this;
    }

    public function isIsProofPrinted(): ?bool
    {
        return $this->isProofPrinted;
    }

    public function setIsProofPrinted(bool $isProofPrinted): static
    {
        $this->isProofPrinted = $isProofPrinted;

        return $this;
    }

    public function getSortIndex(): ?int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(?int $sortIndex): static
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getProofPrintedBy(): ?User
    {
        return $this->proofPrintedBy;
    }

    public function setProofPrintedBy(?User $proofPrintedBy): static
    {
        $this->proofPrintedBy = $proofPrintedBy;

        return $this;
    }

    public function getProofPrintedAt(): ?\DateTimeImmutable
    {
        return $this->proofPrintedAt;
    }

    public function setProofPrintedAt(\DateTimeImmutable $proofPrintedAt): static
    {
        $this->proofPrintedAt = $proofPrintedAt;

        return $this;
    }
}
