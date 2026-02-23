<?php

namespace App\Entity\Admin;

use App\Entity\AdminUser;
use App\Repository\Admin\WarehouseOrderLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WarehouseOrderLogRepository::class)]
class WarehouseOrderLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'warehouseOrderLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WarehouseOrder $order = null;

    #[ORM\ManyToOne]
    #[Groups(['apiData'])]
    private ?AdminUser $loggedBy = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['apiData'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?bool $isManual = null;

    public function __construct()
    {
        $this->isManual = false;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?WarehouseOrder
    {
        return $this->order;
    }

    public function setOrder(?WarehouseOrder $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getLoggedBy(): ?AdminUser
    {
        return $this->loggedBy;
    }

    public function setLoggedBy(?AdminUser $loggedBy): static
    {
        $this->loggedBy = $loggedBy;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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

    public function isIsManual(): ?bool
    {
        return $this->isManual;
    }

    public function setIsManual(bool $isManual): static
    {
        $this->isManual = $isManual;

        return $this;
    }
}
