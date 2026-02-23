<?php

namespace App\Entity;

use App\Enum\OrderShipmentTypeEnum;
use App\Repository\OrderShipmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderShipmentRepository::class)]
class OrderShipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderShipments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shipmentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fromAddressId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $toAddressId = null;

    #[ORM\Column(length: 255)]
    private ?string $parcelId = null;

    #[ORM\Column(nullable: true)]
    private ?array $selectedRate = null;

    #[ORM\Column(nullable: true)]
    private ?array $postageLabel = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $carrier = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $service = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingId = null;

    #[ORM\Column(nullable: true)]
    private array $rates = [];

    #[ORM\Column]
    private array $metaData = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $refundStatus = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $refundMeta = [];

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $internalNotes = null;

    #[ORM\Column]
    private array $tracking = [];

    #[ORM\Column]
    private array $customsInfo = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $purchasedAt = null;

    #[ORM\ManyToOne]
    private ?AdminUser $purchasedBy = null;

    #[ORM\ManyToOne]
    private ?AdminUser $printedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $printedAt = null;

    #[ORM\Column(nullable: true)]
    private ?OrderShipmentTypeEnum $type = null;

    #[ORM\Column]
    private ?int $batchNum = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shipmentOrderId = null;

    public function __construct()
    {
        $this->metaData = [];
        $this->refundMeta = [];
        $this->tracking = [];
        $this->customsInfo = [];
        $this->status = 'pending';
        $this->internalNotes = '';
        $this->printedBy = null;
        $this->printedAt = null;
        $this->purchasedBy = null;
        $this->purchasedAt = null;
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->type = OrderShipmentTypeEnum::DELIVERY;
        $this->batchNum = 1;
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

    public function getShipmentId(): ?string
    {
        return $this->shipmentId;
    }

    public function setShipmentId(?string $shipmentId): static
    {
        $this->shipmentId = $shipmentId;

        return $this;
    }

    public function getFromAddressId(): ?string
    {
        return $this->fromAddressId;
    }

    public function setFromAddressId(?string $fromAddressId): static
    {
        $this->fromAddressId = $fromAddressId;

        return $this;
    }

    public function getToAddressId(): ?string
    {
        return $this->toAddressId;
    }

    public function setToAddressId(?string $toAddressId): static
    {
        $this->toAddressId = $toAddressId;

        return $this;
    }

    public function getParcelId(): ?string
    {
        return $this->parcelId;
    }

    public function setParcelId(string $parcelId): static
    {
        $this->parcelId = $parcelId;

        return $this;
    }

    public function getSelectedRate(): ?array
    {
        return $this->selectedRate;
    }

    public function setSelectedRate(?array $selectedRate): static
    {
        $this->selectedRate = $selectedRate;

        return $this;
    }

    public function getPostageLabel(): ?array
    {
        return $this->postageLabel;
    }

    public function setPostageLabel(?array $postageLabel): static
    {
        $this->postageLabel = $postageLabel;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getTrackingId(): ?string
    {
        return $this->trackingId;
    }

    public function setTrackingId(?string $trackingId): static
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function getMetaDataKey(string $key)
    {
        if (isset($this->metaData[$key])) {
            return $this->metaData[$key];
        }

        return null;
    }

    public function getRates(): array
    {
        return $this->rates;
    }

    public function setRates(?array $rates): static
    {
        $this->rates = $rates;

        return $this;
    }

    public function setMetaData(array $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
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

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(?\DateTimeImmutable $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }

    public function getRefundStatus(): ?string
    {
        return $this->refundStatus;
    }

    public function setRefundStatus(?string $refundStatus): static
    {
        $this->refundStatus = $refundStatus;

        return $this;
    }

    public function getRefundMeta(): array
    {
        return $this->refundMeta;
    }

    public function setRefundMeta(?array $refundMeta): static
    {
        $this->refundMeta = $refundMeta;

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

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): void
    {
        $this->internalNotes = $internalNotes;
    }

    public function getTracking(): array
    {
        return $this->tracking;
    }

    public function setTracking(array $tracking): static
    {
        $this->tracking = $tracking;

        return $this;
    }

    public function getCustomsInfo(): array
    {
        return $this->customsInfo;
    }

    public function setCustomsInfo(array $customsInfo): static
    {
        $this->customsInfo = $customsInfo;

        return $this;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(?\DateTimeImmutable $purchasedAt): static
    {
        $this->purchasedAt = $purchasedAt;

        return $this;
    }

    public function getPurchasedBy(): ?AdminUser
    {
        return $this->purchasedBy;
    }

    public function setPurchasedBy(?AdminUser $purchasedBy): static
    {
        $this->purchasedBy = $purchasedBy;

        return $this;
    }

    public function getPrintedBy(): ?AdminUser
    {
        return $this->printedBy;
    }

    public function setPrintedBy(?AdminUser $printedBy): static
    {
        $this->printedBy = $printedBy;

        return $this;
    }

    public function getPrintedAt(): ?\DateTimeImmutable
    {
        return $this->printedAt;
    }

    public function setPrintedAt(?\DateTimeImmutable $printedAt): static
    {
        $this->printedAt = $printedAt;

        return $this;
    }

    public function getType(): ?OrderShipmentTypeEnum
    {
        return $this->type;
    }

    public function setType(OrderShipmentTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getBatchNum(): ?int
    {
        return $this->batchNum;
    }

    public function setBatchNum(int $batchNum): static
    {
        $this->batchNum = $batchNum;

        return $this;
    }

    public function getShipmentOrderId(): ?string
    {
        return $this->shipmentOrderId;
    }

    public function setShipmentOrderId(?string $shipmentOrderId): static
    {
        $this->shipmentOrderId = $shipmentOrderId;

        return $this;
    }


}
