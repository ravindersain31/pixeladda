<?php

namespace App\Entity\Admin;

use App\Repository\Admin\WarehouseShipByListRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: WarehouseShipByListRepository::class)]
class WarehouseShipByList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['apiData'])]
    private ?\DateTimeInterface $shipBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(length: 30)]
    #[Groups(['apiData'])]
    private ?string $printerName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['apiData'])]
    private ?int $sortIndex = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSortIndex(): void
    {
        if ($this->shipBy instanceof \DateTimeInterface) {
            // Set sortIndex as the UNIX timestamp of shipBy date
            $this->sortIndex = (int) $this->shipBy->format('U');
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getPrinterName(): ?string
    {
        return $this->printerName;
    }

    public function setPrinterName(string $printerName): static
    {
        $this->printerName = $printerName;

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
}
