<?php

namespace App\Entity\Reports;

use App\Entity\Order;
use App\Repository\Reports\OrderCogsReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderCogsReportRepository::class)]
class OrderCogsReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderCogsReports')]
    private ?Order $relatedOrder = null;

    #[ORM\ManyToOne(inversedBy: 'orderCogsReports')]
    private ?DailyCogsReport $dailyCogsReport = null;

    #[ORM\Column(nullable: true)]
    private ?float $materialCost = null;

    #[ORM\Column(nullable: true)]
    private ?array $materialCostBreakdown = null;

    #[ORM\Column(nullable: true)]
    private ?float $laborCost = null;

    #[ORM\Column(nullable: true)]
    private ?float $refundedAmount = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isEdit = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isReset = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelatedOrder(): ?Order
    {
        return $this->relatedOrder;
    }

    public function setRelatedOrder(?Order $relatedOrder): static
    {
        $this->relatedOrder = $relatedOrder;

        return $this;
    }

    public function getDailyCogsReport(): ?DailyCogsReport
    {
        return $this->dailyCogsReport;
    }

    public function setDailyCogsReport(?DailyCogsReport $dailyCogsReport): static
    {
        $this->dailyCogsReport = $dailyCogsReport;

        return $this;
    }

    public function getMaterialCost(): ?float
    {
        return $this->materialCost;
    }

    public function setMaterialCost(?float $materialCost): static
    {
        $this->materialCost = $materialCost;

        return $this;
    }

    public function getMaterialCostBreakdown(): ?array
    {
        return $this->materialCostBreakdown;
    }

    public function setMaterialCostBreakdown(?array $materialCostBreakdown): static
    {
        $this->materialCostBreakdown = $materialCostBreakdown;

        return $this;
    }

    public function getLaborCost(): ?float
    {
        return $this->laborCost;
    }

    public function setLaborCost(?float $laborCost): static
    {
        $this->laborCost = $laborCost;

        return $this;
    }

    public function getRefundedAmount(): ?float
    {
        return $this->refundedAmount;
    }

    public function setRefundedAmount(?float $refundedAmount): static
    {
        $this->refundedAmount = $refundedAmount;

        return $this;
    }

    public function isEdit(): ?bool
    {
        return $this->isEdit;
    }

    public function setIsEdit(?bool $isEdit): static
    {
        $this->isEdit = $isEdit;

        return $this;
    }

    public function isReset(): ?bool
    {
        return $this->isReset;
    }

    public function setIsReset(?bool $isReset): static
    {
        $this->isReset = $isReset;

        return $this;
    }
}
