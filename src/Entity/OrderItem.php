<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('apiCanvasData')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $addOnsAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $shippingAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $unitAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups('apiCanvasData')]
    private array $addOns = [];

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveryDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups('apiCanvasData')]
    private array $canvasData = [];

    #[ORM\Column]
    private array $shipping = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cartItemId = null;

    #[ORM\Column]
    #[Groups('apiCanvasData')]
    private array $metaData = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $itemName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $itemDescription = null;

    #[ORM\Column(length: 255)]
    private ?string $itemType = null;

    public const DEFAULT = 'DEFAULT';
    public const CHARGED_ITEM = 'CHARGED_ITEM';
    public const DISCOUNT_ITEM = 'DISCOUNT_ITEM';
    public const COMMENT_ITEM = 'COMMENT_ITEM';

    public const ITEMSTATUS = [
        self::DEFAULT => self::DEFAULT ,
        self::CHARGED_ITEM => self::CHARGED_ITEM,
        self::DISCOUNT_ITEM => self::DISCOUNT_ITEM,
        self::COMMENT_ITEM => self::COMMENT_ITEM,
    ];

    public function __construct()
    {
        $this->canvasData = [];
        $this->shipping = [];
        $this->addOnsAmount = 0;
        $this->shippingAmount = 0;
        $this->totalAmount = 0;
        $this->addOns = [];
        $this->metaData = [];
        $this->itemType = 'DEFAULT';
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getAddOnsAmount(): ?string
    {
        return $this->addOnsAmount;
    }

    public function setAddOnsAmount(string $addOnsAmount): static
    {
        $this->addOnsAmount = $addOnsAmount;

        return $this;
    }

    public function getShippingAmount(): ?string
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(string $shippingAmount): static
    {
        $this->shippingAmount = $shippingAmount;

        return $this;
    }

    public function getUnitAmount(): ?string
    {
        return $this->unitAmount;
    }

    public function setUnitAmount(string $unitAmount): static
    {
        $this->unitAmount = $unitAmount;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getAddOns(): array
    {
        return $this->addOns;
    }

    public function setAddOns(array $addOns): static
    {
        $this->addOns = $addOns;

        return $this;
    }

    public function getDeliveryDate(): ?\DateTimeInterface
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?\DateTimeInterface $deliveryDate): static
    {
        $this->deliveryDate = $deliveryDate;

        return $this;
    }

    public function getCanvasData(): array
    {
        return $this->canvasData;
    }

    public function setCanvasData(?array $canvasData): static
    {
        $this->canvasData = $canvasData;

        return $this;
    }

    public function getShipping(): array
    {
        return $this->shipping;
    }

    public function setShipping(array $shipping): static
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getCartItemId(): ?string
    {
        return $this->cartItemId;
    }

    public function setCartItemId(?string $cartItemId): static
    {
        $this->cartItemId = $cartItemId;

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

    public function getItemName(): ?string
    {
        return $this->itemName;
    }

    public function setItemName(?string $itemName): static
    {
        $this->itemName = $itemName;

        return $this;
    }

    public function getItemDescription(): ?string
    {
        return $this->itemDescription;
    }

    public function setItemDescription(?string $itemDescription): static
    {
        $this->itemDescription = $itemDescription;

        return $this;
    }

    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): static
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function IsWireStake(): bool
    {
        $product = $this->getProduct();
        if ($product === null) {
            return false;
        }

        $parent = $product->getParent();
        if ($parent === null) {
            return false;
        }

        return $parent->getSku() === 'WIRE-STAKE' || $this->getMetaDataKey('isWireStake');
    }
}
