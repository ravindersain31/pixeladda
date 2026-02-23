<?php

namespace App\Entity\Reports;

use App\Repository\Reports\DailyCogsReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyCogsReportRepository::class)]
class DailyCogsReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'dailyCogsReports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MonthlyCogsReport $month = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $googleAdsSpent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $facebookAdsSpent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $bingAdsSpent = null;

    #[ORM\Column]
    private ?int $totalOrders = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalSales = null;

    #[ORM\Column]
    private ?int $totalPaidOrders = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPaidSales = null;

    #[ORM\Column]
    private ?int $totalPayLaterOrder = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPayLaterSales = null;

    #[ORM\Column]
    private ?int $totalRefundedOrder = null;

    #[ORM\Column(nullable: true)]
    private ?array $refundedOrders = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalRefundedAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $materialCost = null;

    #[ORM\Column(type: Types::JSON)]
    private array $materialCostBreakdown = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private array $googleAdsData = [];

    #[ORM\Column]
    private array $bingAdsData = [];

    #[ORM\Column]
    private array $facebookAdsData = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalShippingCost = null;

    #[ORM\Column]
    private ?bool $hasCustomData = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPaymentLinkAmount = null;

    #[ORM\Column(nullable: true)]
    private ?array $cancelledOrders = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cancelledSales = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalCheckSales = null;

    #[ORM\Column]
    private ?int $totalCheckOrders = null;

    #[ORM\Column(nullable: true, type: Types::JSON)]
    private ?array $shippingCostBreakDown = null;

    #[ORM\Column(nullable: true)]
    private ?float $laborCost = null;

    /**
     * @var Collection<int, OrderCogsReport>
     */
    #[ORM\OneToMany(targetEntity: OrderCogsReport::class, mappedBy: 'dailyCogsReport')]
    private Collection $orderCogsReports;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $originalMaterialCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $originalLaborCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $originalRefundedAmount = null;

    public function __construct()
    {
        $this->googleAdsSpent = 0;
        $this->facebookAdsSpent = 0;
        $this->bingAdsSpent = 0;
        $this->totalOrders = 0;
        $this->totalSales = 0;
        $this->totalPaidOrders = 0;
        $this->totalPaidSales = 0;
        $this->totalPaymentLinkAmount = 0;
        $this->totalPayLaterOrder = 0;
        $this->totalPayLaterSales = 0;
        $this->totalRefundedOrder = 0;
        $this->refundedOrders = [];
        $this->totalRefundedAmount = 0;
        $this->totalShippingCost = 0;
        $this->materialCost = 0;
        $this->materialCostBreakdown = [];
        $this->shippingCostBreakDown = [
            'shippingCharges' => 0,
            'shippingAdjustment' => 0,
            'shippingTotal' => 0
        ];
        $this->hasCustomData = false;
        $this->totalCheckSales = 0;
        $this->totalCheckOrders = 0;
        $this->cancelledSales = 0;
        $this->cancelledOrders = [];
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->orderCogsReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): ?MonthlyCogsReport
    {
        return $this->month;
    }

    public function setMonth(?MonthlyCogsReport $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getGoogleAdsSpent(): ?string
    {
        return $this->googleAdsSpent;
    }

    public function setGoogleAdsSpent(string $googleAdsSpent): static
    {
        $this->googleAdsSpent = $googleAdsSpent;

        return $this;
    }

    public function getFacebookAdsSpent(): ?string
    {
        return $this->facebookAdsSpent;
    }

    public function setFacebookAdsSpent(string $facebookAdsSpent): static
    {
        $this->facebookAdsSpent = $facebookAdsSpent;

        return $this;
    }

    public function getBingAdsSpent(): ?string
    {
        return $this->bingAdsSpent;
    }

    public function setBingAdsSpent(string $bingAdsSpent): static
    {
        $this->bingAdsSpent = $bingAdsSpent;

        return $this;
    }

    public function getTotalAdsCost(): float
    {
        return $this->googleAdsSpent + $this->facebookAdsSpent + $this->bingAdsSpent;
    }

    public function getTotalCost(): float
    {
        return $this->getTotalAdsCost() + $this->materialCost + $this->totalShippingCost + $this->getTotalLaborCost();
    }

    public function getTotalOrders(): ?int
    {
        return $this->totalOrders;
    }

    public function setTotalOrders(int $totalOrders): static
    {
        $this->totalOrders = $totalOrders;

        return $this;
    }

    public function getTotalSales(): ?string
    {
        return $this->totalSales;
    }

    public function setTotalSales(string $totalSales): static
    {
        $this->totalSales = $totalSales;

        return $this;
    }

    public function getTotalPaidOrders(): ?int
    {
        return $this->totalPaidOrders;
    }

    public function setTotalPaidOrders(int $totalPaidOrders): static
    {
        $this->totalPaidOrders = $totalPaidOrders;

        return $this;
    }

    public function getTotalPaidSales(): ?string
    {
        return $this->totalPaidSales;
    }

    public function setTotalPaidSales(string $totalPaidSales): static
    {
        $this->totalPaidSales = $totalPaidSales;

        return $this;
    }

    public function getTotalPayLaterOrder(): ?int
    {
        return $this->totalPayLaterOrder;
    }

    public function setTotalPayLaterOrder(int $totalPayLaterOrder): static
    {
        $this->totalPayLaterOrder = $totalPayLaterOrder;

        return $this;
    }

    public function getTotalPayLaterSales(): ?string
    {
        return $this->totalPayLaterSales;
    }

    public function setTotalPayLaterSales(string $totalPayLaterSales): static
    {
        $this->totalPayLaterSales = $totalPayLaterSales;

        return $this;
    }

    public function getTotalRefundedOrder(): ?int
    {
        return $this->totalRefundedOrder;
    }

    public function setTotalRefundedOrder(int $totalRefundedOrder): static
    {
        $this->totalRefundedOrder = $totalRefundedOrder;

        return $this;
    }

    public function getRefundedOrders(): array
    {
        return $this->refundedOrders;
    }

    public function setRefundedOrders(?array $refundedOrders): static
    {
        $this->refundedOrders = $refundedOrders;

        return $this;
    }

    public function getTotalRefundedAmount(): ?string
    {
        return $this->totalRefundedAmount;
    }

    public function setTotalRefundedAmount(string $totalRefundedAmount): static
    {
        $this->totalRefundedAmount = $totalRefundedAmount;

        return $this;
    }

    public function getMaterialCost(): ?string
    {
        return $this->materialCost;
    }

    public function setMaterialCost(string $materialCost): static
    {
        $this->materialCost = $materialCost;

        return $this;
    }

    public function getMaterialCostBreakdown(): array
    {
        return $this->materialCostBreakdown;
    }

    public function setMaterialCostBreakdown(array $materialCostBreakdown): static
    {
        $this->materialCostBreakdown = $materialCostBreakdown;

        return $this;
    }

    public function profitAndLoss(): float
    {
        $totalCost = $this->getTotalCost();
        return $this->getTotalPaidSales() - ($totalCost + $this->totalRefundedAmount);
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

    public function getGoogleAdsData(): array
    {
        return $this->googleAdsData;
    }

    public function setGoogleAdsData(array $googleAdsData): static
    {
        $this->googleAdsData = $googleAdsData;

        return $this;
    }

    public function getBingAdsData(): array
    {
        return $this->bingAdsData;
    }

    public function setBingAdsData(array $bingAdsData): static
    {
        $this->bingAdsData = $bingAdsData;

        return $this;
    }

    public function getFacebookAdsData(): array
    {
        return $this->facebookAdsData;
    }

    public function setFacebookAdsData(array $facebookAdsData): static
    {
        $this->facebookAdsData = $facebookAdsData;

        return $this;
    }

    public function getTotalShippingCost(): ?string
    {
        return $this->totalShippingCost;
    }

    public function setTotalShippingCost(string $totalShippingCost): static
    {
        $this->totalShippingCost = $totalShippingCost;

        return $this;
    }

    public function isHasCustomData(): ?bool
    {
        return $this->hasCustomData;
    }

    public function setHasCustomData(bool $hasCustomData): static
    {
        $this->hasCustomData = $hasCustomData;

        return $this;
    }

    public function getTotalPaymentLinkAmount(): ?string
    {
        return $this->totalPaymentLinkAmount;
    }

    public function setTotalPaymentLinkAmount(string $totalPaymentLinkAmount): static
    {
        $this->totalPaymentLinkAmount = $totalPaymentLinkAmount;

        return $this;
    }

    public function getCancelledOrders(): array
    {
        return $this->cancelledOrders;
    }

    public function setCancelledOrders(?array $cancelledOrders): static
    {
        $this->cancelledOrders = $cancelledOrders;

        return $this;
    }

    public function getCancelledSales(): ?string
    {
        return $this->cancelledSales;
    }

    public function setCancelledSales(string $cancelledSales): static
    {
        $this->cancelledSales = $cancelledSales;

        return $this;
    }

    public function getTotalCheckSales(): ?string
    {
        return $this->totalCheckSales;
    }

    public function setTotalCheckSales(string $totalCheckSales): static
    {
        $this->totalCheckSales = $totalCheckSales;

        return $this;
    }

    public function getTotalCheckOrders(): ?int
    {
        return $this->totalCheckOrders;
    }

    public function setTotalCheckOrders(int $totalCheckOrders): static
    {
        $this->totalCheckOrders = $totalCheckOrders;

        return $this;
    }

    public function getShippingCostBreakDown(): ?array
    {
        return $this->shippingCostBreakDown;
    }

    public function setShippingCostBreakDown(?array $shippingCostBreakDown): static
    {
        $defaultShippingCostBreakDown = [
            'shippingCharges' => 0,
            'shippingAdjustment' => 0,
            'shippingTotal' => 0
        ];

        $this->shippingCostBreakDown = array_merge($defaultShippingCostBreakDown, $shippingCostBreakDown);

        return $this;
    }

    public function getTotalRecievedAmount(): ?float
    {
        return $this->totalPaidSales - $this->totalRefundedAmount;
    }

    public function getNetMargin(): ?float
    {
        $totalCost = $this->getTotalCost();

        return $this->getTotalPaidSales() - ($totalCost + $this->totalRefundedAmount);
    }

    public function getNetMarginPercentage(): ?float
    {
        $netMarginPercentage = $this->totalPaidSales != 0 ? (((float) $this->getNetMargin()) / $this->totalPaidSales) * 100 : 0;
        return $netMarginPercentage;
    }

    public function getGrossMargin(): ?float
    {
        $totalLaborCost = $this->getTotalLaborCost() ?? 0;

        $grossMargin = $this->getTotalPaidSales() - ($this->totalRefundedAmount + $this->materialCost + $this->totalShippingCost + $totalLaborCost);
        return $grossMargin;
    }

    public function getGrossMarginPercentage(): ?float
    {
        $grossMarginPercentage = $this->totalPaidSales != 0 ? (((float) $this->getGrossMargin()) / $this->totalPaidSales) * 100 : 0;
        return $grossMarginPercentage;
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

    public function getTotalLaborCost(): ?float
    {
        if ($this->laborCost > 0) {
            return $this->laborCost;
        }

        $laborCost = $this->getMaterialCost()['totalLaborCost'] ?? 0;

        return $laborCost;
    }

    /**
     * @return Collection<int, OrderCogsReport>
     */
    public function getOrderCogsReports(): Collection
    {
        return $this->orderCogsReports;
    }

    public function addOrderCogsReport(OrderCogsReport $orderCogsReport): static
    {
        if (!$this->orderCogsReports->contains($orderCogsReport)) {
            $this->orderCogsReports->add($orderCogsReport);
            $orderCogsReport->setDailyCogsReport($this);
        }

        return $this;
    }

    public function removeOrderCogsReport(OrderCogsReport $orderCogsReport): static
    {
        if ($this->orderCogsReports->removeElement($orderCogsReport)) {
            // set the owning side to null (unless already changed)
            if ($orderCogsReport->getDailyCogsReport() === $this) {
                $orderCogsReport->setDailyCogsReport(null);
            }
        }

        return $this;
    }

    public function getOriginalMaterialCost(): ?string
    {
        return $this->originalMaterialCost;
    }

    public function setOriginalMaterialCost(?string $originalMaterialCost): static
    {
        $this->originalMaterialCost = $originalMaterialCost;

        return $this;
    }

    public function getOriginalLaborCost(): ?string
    {
        return $this->originalLaborCost;
    }

    public function setOriginalLaborCost(?string $originalLaborCost): static
    {
        $this->originalLaborCost = $originalLaborCost;

        return $this;
    }

    public function getOriginalRefundedAmount(): ?string
    {
        return $this->originalRefundedAmount;
    }

    public function setOriginalRefundedAmount(?string $originalRefundedAmount): static
    {
        $this->originalRefundedAmount = $originalRefundedAmount;

        return $this;
    }

}
