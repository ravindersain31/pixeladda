<?php

namespace App\Entity\Admin;

use App\Entity\Admin\Cogs\ShippingInvoiceFile;
use App\Entity\Order;
use App\Repository\Admin\ShippingInvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShippingInvoiceRepository::class)]
class ShippingInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'shippingInvoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(nullable: true)]
    private ?array $referenceNumbers = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $invoiceSection = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $invoiceType = null;

    #[ORM\Column(nullable: true)]
    private ?float $billedCharge = null;

    #[ORM\ManyToOne(targetEntity: ShippingInvoiceFile::class, inversedBy: 'shippingInvoices', cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: true)]
    private ?ShippingInvoiceFile $file = null;

    public const INVOICE_TYPE_OUTBOUND = 'OUTBOUND';
    public const INVOICE_TYPE_ADJUSTMENT = 'ADJUSTMENT';
    public const INVOICE_TYPE_TOTAL = 'TOTAL';

    public function __construct()
    {
        // Set default structures for JSON fields
        $this->referenceNumbers = [
            'ref1' => null,
            'ref2' => null,
            'ref3' => null,
        ];
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

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getReferenceNumbers(): ?array
    {
        return $this->referenceNumbers;
    }

    public function setReferenceNumbers(array $referenceNumbers): self
    {
        // Validate the structure before setting
        $defaultStructure = [
            'ref1' => null,
            'ref2' => null,
            'ref3' => null,
        ];
        $this->referenceNumbers = array_merge($defaultStructure, $referenceNumbers);
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getInvoiceSection(): ?string
    {
        return $this->invoiceSection;
    }

    public function setInvoiceSection(?string $invoiceSection): static
    {
        $this->invoiceSection = $invoiceSection;

        return $this;
    }

    public function getInvoiceType(): ?string
    {
        return $this->invoiceType;
    }

    public function setInvoiceType(?string $invoiceType): static
    {
        $this->invoiceType = $invoiceType;

        return $this;
    }

    public function getBilledCharge(): ?float
    {
        return $this->billedCharge;
    }

    public function setBilledCharge(?float $billedCharge): static
    {
        $this->billedCharge = $billedCharge;

        return $this;
    }

    public function isAdjustments(): bool
    {
        if($this->invoiceType === ShippingInvoice::INVOICE_TYPE_ADJUSTMENT) {
            return true;
        }
        return false;
    }

    public function getFile(): ?ShippingInvoiceFile
    {
        return $this->file;
    }

    public function setFile(?ShippingInvoiceFile $file): static
    {
        $this->file = $file;

        return $this;
    }
}
