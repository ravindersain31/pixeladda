<?php

namespace App\Entity;

use App\Entity\Admin\Coupon;
use App\Enum\ProductEnum;
use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    const ORDER_PROTECTION_PERCENTAGE = 20;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $cartId = null;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, cascade: ['persist'])]
    private Collection $cartItems;

    #[ORM\Column]
    private array $data = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $orderProtection = null;

    #[ORM\Column]
    private ?bool $internationalShippingCharge = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $orderProtectionAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $internationalShippingChargeAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $subTotal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\ManyToOne]
    private ?Coupon $coupon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $couponAmount = null;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: SavedCart::class)]
    private Collection $savedCarts;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: EmailQuote::class)]
    private Collection $emailQuotes;

    #[ORM\ManyToOne]
    private ?Store $store = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalShipping = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0')]
    private ?string $totalQuantity = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $additionalDiscount = [];

    #[ORM\Column(nullable: true)]
    private ?bool $needProof = null;

    #[ORM\Column(nullable: true)]
    private ?bool $designApproved = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->data = [];
        $this->cartItems = new ArrayCollection();
        $this->orderProtection = false;
        $this->internationalShippingCharge = false;
        $this->orderProtectionAmount = 0;
        $this->internationalShippingChargeAmount = 0;
        $this->couponAmount = 0;
        $this->subTotal = 0;
        $this->totalShipping = 0;
        $this->totalAmount = 0;
        $this->totalQuantity = 0;
        $this->orders = new ArrayCollection();
        $this->savedCarts = new ArrayCollection();
        $this->emailQuotes = new ArrayCollection();
        $this->version = "V2";
        $this->additionalDiscount = [];
        $this->needProof = true;
        $this->designApproved = false;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->setUpdatedAt(new \DateTimeImmutable());
            $this->setCreatedAt(new \DateTimeImmutable());
            $currentCart = $this;
            $this->cartItems = $this->cartItems->map(function (CartItem $cartItem) use ($currentCart) {
                $newItem = clone $cartItem;
                $newItem->setCart($currentCart);
                return $newItem;
            });
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCartId(): ?string
    {
        return $this->cartId;
    }

    public function setCartId(string $cartId): static
    {
        $this->cartId = $cartId;

        return $this;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function setCartItems(ArrayCollection $cartItems): static
    {
        $this->cartItems = $cartItems;
        return $this;
    }

    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setCart($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            // set the owning side to null (unless already changed)
            if ($cartItem->getCart() === $this) {
                $cartItem->setCart(null);
            }
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        // prevent saving items in cart data as we already saving it as separate entity with relation to cart;
        if (isset($data['items'])) unset($data['items']);
        if (isset($data['subTotalAmount'])) unset($data['subTotalAmount']);
        if (isset($data['totalAmount'])) unset($data['totalAmount']);
        if (isset($data['totalQuantity'])) unset($data['totalQuantity']);
        if (isset($data['totalShipping'])) unset($data['totalShipping']);
        if (isset($data['readyForCart'])) unset($data['readyForCart']);

        $this->data = $data;

        return $this;
    }

    public function getDataKey(string $key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    public function setDataKey(string $key, $value): self
    {
        $data = $this->data;
        $data[$key] = $value;
        $this->data = $data;

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

    public function isOrderProtection(): ?bool
    {
        return $this->orderProtection;
    }

    public function setOrderProtection(bool $orderProtection): static
    {
        $this->orderProtection = $orderProtection;

        return $this;
    }

    public function isInternationalShippingCharge(): ?bool
    {
        return $this->internationalShippingCharge;
    }

    public function setInternationalShippingCharge(bool $internationalShippingCharge): static
    {
        $this->internationalShippingCharge = $internationalShippingCharge;

        return $this;
    }
    public function getOrderProtectionAmount(): ?string
    {
        return $this->orderProtectionAmount;
    }

    public function setOrderProtectionAmount(string $orderProtectionAmount): static
    {
        $this->orderProtectionAmount = $orderProtectionAmount;

        return $this;
    }

    public function getInternationalShippingChargeAmount(): ?string
    {
        return $this->internationalShippingChargeAmount;
    }

    public function setInternationalShippingChargeAmount(string $internationalShippingChargeAmount): static
    {
        $this->internationalShippingChargeAmount = $internationalShippingChargeAmount;

        return $this;
    }

    public function getSubTotal(): ?string
    {
        return $this->subTotal;
    }

    public function setSubTotal(string $subTotal): static
    {
        $this->subTotal = $subTotal;

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

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCart($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCart() === $this) {
                $order->setCart(null);
            }
        }

        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function getCouponAmount(): ?string
    {
        return $this->couponAmount;
    }

    public function setCouponAmount(?string $couponAmount): static
    {
        $this->couponAmount = $couponAmount;

        return $this;
    }

    /**
     * @return Collection<int, SavedCart>
     */
    public function getSavedCarts(): Collection
    {
        return $this->savedCarts;
    }

    public function addSavedCart(SavedCart $savedCart): static
    {
        if (!$this->savedCarts->contains($savedCart)) {
            $this->savedCarts->add($savedCart);
            $savedCart->setCart($this);
        }

        return $this;
    }

    public function removeSavedCart(SavedCart $savedCart): static
    {
        if ($this->savedCarts->removeElement($savedCart)) {
            // set the owning side to null (unless already changed)
            if ($savedCart->getCart() === $this) {
                $savedCart->setCart(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmailQuote>
     */
    public function getemailQuotes(): Collection
    {
        return $this->emailQuotes;
    }

    public function addSavedQuote(EmailQuote $savedQuote): static
    {
        if (!$this->emailQuotes->contains($savedQuote)) {
            $this->emailQuotes->add($savedQuote);
            $savedQuote->setCart($this);
        }

        return $this;
    }

    public function removeSavedQuote(EmailQuote $savedQuote): static
    {
        if ($this->emailQuotes->removeElement($savedQuote)) {
            // set the owning side to null (unless already changed)
            if ($savedQuote->getCart() === $this) {
                $savedQuote->setCart(null);
            }
        }

        return $this;
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

    public function getTotalShipping(): ?string
    {
        return $this->totalShipping;
    }

    public function setTotalShipping(string $totalShipping): static
    {
        $this->totalShipping = $totalShipping;

        return $this;
    }

    public function getTotalQuantity(): ?string
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(string $totalQuantity): static
    {
        $this->totalQuantity = $totalQuantity;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version ?? "V2";
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getAdditionalDiscount(): ?array
    {
        return $this->additionalDiscount;
    }

    public function setAdditionalDiscount(?array $additionalDiscount): static
    {
        $this->additionalDiscount = $additionalDiscount;

        return $this;
    }

    public function getAdditionalDiscountKey(string $key): ?array
    {
        return $this->additionalDiscount[$key] ?? null;
    }

    public function setAdditionalDiscountKey(string $key, string $type = 'FIXED', float $amount = 0, string $name = 'Discount'): static
    {
        if (isset($this->additionalDiscount[$key])) {
            $this->additionalDiscount[$key]['name'] = $name;
            $this->additionalDiscount[$key]['type'] = $type;
            $this->additionalDiscount[$key]['amount'] = $amount;
        } else {
            $this->additionalDiscount[$key] = [
                'name' => $name,
                'type' => $type,
                'amount' => $amount
            ];
        }

        return $this;
    }

    public function removeAdditionalDiscountKey(string $key): static
    {
        unset($this->additionalDiscount[$key]);
        return $this;
    }

    public function isNeedProof(): ?bool
    {
        return $this->needProof;
    }

    public function setNeedProof(?bool $needProof): static
    {
        $this->needProof = $needProof;

        return $this;
    }

    public function isDesignApproved(): ?bool
    {
        return $this->designApproved;
    }

    public function setDesignApproved(?bool $designApproved): static
    {
        $this->designApproved = $designApproved;

        return $this;
    }

    public function isSample(): bool
    {
        foreach ($this->getCartItems() as $cartItem) {
            $template = $cartItem->getProduct()->getParent()->getSku();

            if ($template !== ProductEnum::SAMPLE->value) {
                $metaData = $cartItem->getDataKey('customSize');
                if (!isset($metaData['parentSku']) || $metaData['parentSku'] !== ProductEnum::SAMPLE->value) {
                    return false;
                }
            }
        }
        return true;
    }

    public function hasSample(): bool
    {
        foreach ($this->getCartItems() as $cartItem) {
            $sku = $cartItem->getProduct()->getSku();
            if (str_contains($sku, ProductEnum::SAMPLE->value)) {
                return true;
            }
        }
        return false;
    }

    public function isWireStake(): bool
    {
        $isContainsWireStake = false;
        foreach ($this->getCartItems() as $cartItem) {
            $template = $cartItem->getProduct()->getParent()->getSku();
            if ($template !== ProductEnum::WIRE_STAKE->value) {
                $isContainsWireStake = true;
            }
        }
        return !$isContainsWireStake;
    }

    public function isWireStakeAndSampleAndBlankSign(): bool
    {
        $isContainsWireStakeAndSample = false;
        foreach ($this->getCartItems() as $cartItem) {
            $template = $cartItem->getProduct()->getParent()->getSku();
            if (!in_array($template, [ProductEnum::WIRE_STAKE->value,ProductEnum::SAMPLE->value,ProductEnum::BLANK_SIGN->value], true)) {
                $isContainsWireStakeAndSample = true;
            }
        }
        return !$isContainsWireStakeAndSample;
    }

    public function isBlankSign(): bool
    {
        $isContainsBlankSign = false;
        foreach ($this->getCartItems() as $cartItem) {
            $template = $cartItem->getProduct()->getParent()->getSku();
            if ($template !== ProductEnum::BLANK_SIGN->value) {
                $isContainsBlankSign = true;
            }
        }
        return !$isContainsBlankSign;
    }

}
