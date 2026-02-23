<?php

namespace App\Entity\Reports;

use App\Entity\Store;
use App\Repository\Reports\MonthlyCogsReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonthlyCogsReportRepository::class)]
class MonthlyCogsReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $payrollCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $fixedCost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'month', targetEntity: DailyCogsReport::class)]
    private Collection $dailyCogsReports;

    #[ORM\Column(nullable: true)]
    private ?array $lineItems = null;

    public function __construct()
    {
        $this->payrollCost = 0;
        $this->fixedCost = 0;
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->dailyCogsReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

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

    public function getMonth(): ?string
    {
        return $this->date->format('Y-m');
    }

    public function getPayrollCost(): ?string
    {
        return $this->payrollCost;
    }

    public function setPayrollCost(?string $payrollCost): static
    {
        $this->payrollCost = $payrollCost ?? 0;

        return $this;
    }

    public function getFixedCost(): ?string
    {
        return $this->fixedCost;
    }

    public function setFixedCost(?string $fixedCost): static
    {
        $this->fixedCost = $fixedCost ?? 0;

        return $this;
    }

    public function monthlyExpense(): float
    {
        return floatval($this->payrollCost) + floatval($this->fixedCost) + floatval($this->extraExpense());
    }

    public function extraExpense(): float
    {
        if(empty($this->lineItems) || !is_array($this->lineItems)) {
            return 0;
        }
        return array_sum(array_column($this->lineItems, 'value')) ?? 0;
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
     * @return Collection<int, DailyCogsReport>
     */
    public function getDailyCogsReports(): Collection
    {
        return $this->dailyCogsReports;
    }

    public function addDailyCogsReport(DailyCogsReport $dailyCogsReport): static
    {
        if (!$this->dailyCogsReports->contains($dailyCogsReport)) {
            $this->dailyCogsReports->add($dailyCogsReport);
            $dailyCogsReport->setMonth($this);
        }

        return $this;
    }

    public function removeDailyCogsReport(DailyCogsReport $dailyCogsReport): static
    {
        if ($this->dailyCogsReports->removeElement($dailyCogsReport)) {
            // set the owning side to null (unless already changed)
            if ($dailyCogsReport->getMonth() === $this) {
                $dailyCogsReport->setMonth(null);
            }
        }

        return $this;
    }

    public function getLineItems(): ?array
    {
        return $this->lineItems;
    }

    public function setLineItems(?array $lineItems): static
    {
        $this->lineItems = $lineItems;

        return $this;
    }

}
