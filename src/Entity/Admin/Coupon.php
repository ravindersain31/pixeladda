<?php

namespace App\Entity\Admin;

use App\Entity\AppAppUser;
use App\Entity\Order;
use App\Entity\Store;
use App\Entity\AppUser;
use App\Enum\CouponTypeEnum;
use App\Repository\Admin\CouponRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[Constraints\UniqueEntity(fields: 'code', message: 'The coupon code must be unique.')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $couponName = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code = null;

    #[ORM\Column]
    private ?float $discount = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?int $usesTotal = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $isEnabled = null;

    #[ORM\ManyToOne(inversedBy: 'coupon')]
    private ?Store $store = null;

    #[ORM\Column(nullable: true)]
    private ?int $minCartValue = null;

    #[ORM\OneToMany(mappedBy: 'coupon', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\Column(enumType: CouponTypeEnum::class, nullable: true)]
    private ?CouponTypeEnum $couponType = null;

    #[ORM\Column(nullable: true)]
    private ?float $maximumDiscount = null;

    #[ORM\Column(nullable: true)]
    private ?int $minimumQuantity = null;

    #[ORM\Column(nullable: true)]
    private ?int $maximumQuantity = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPromotional = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $deviceIp = null;

    #[ORM\ManyToOne(inversedBy: 'coupons')]
    private ?AppUser $user = null;

    public function __construct()
    {
        $this->startDate = new \DateTimeImmutable;
        $this->endDate = new \DateTimeImmutable;
        $this->createdAt = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
        $this->orders = new ArrayCollection();
        $this->isEnabled = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCouponName(): ?string
    {
        return $this->couponName;
    }

    public function setCouponName(?string $couponName): static
    {
        $this->couponName = $couponName;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUsesTotal(): ?int
    {
        return $this->usesTotal;
    }

    public function setUsesTotal(?int $usesTotal): static
    {
        $this->usesTotal = $usesTotal;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

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

    public function getMinCartValue(): ?int
    {
        return $this->minCartValue;
    }

    public function setMinCartValue(?int $minCartValue): static
    {
        $this->minCartValue = $minCartValue;

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
            $order->setCoupon($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCoupon() === $this) {
                $order->setCoupon(null);
            }
        }

        return $this;
    }

    public function getCouponType(): ?CouponTypeEnum
    {
        return $this->couponType;
    }

    public function setCouponType(?CouponTypeEnum $couponType): static
    {
        $this->couponType = $couponType;
        return $this;
    }

    public function getMaximumDiscount(): ?float
    {
        return $this->maximumDiscount;
    }

    public function setMaximumDiscount(?float $maximumDiscount): static
    {
        $this->maximumDiscount = $maximumDiscount;

        return $this;
    }

    public function getMinimumQuantity(): ?int
    {
        return $this->minimumQuantity;
    }

    public function setMinimumQuantity(?int $minimumQuantity): static
    {
        $this->minimumQuantity = $minimumQuantity;
        return $this;
    }

    public function getMaximumQuantity(): ?int
    {
        return $this->maximumQuantity;
    }

    public function setMaximumQuantity(?int $maximumQuantity): static
    {
        $this->maximumQuantity = $maximumQuantity;

        return $this;
    }

    public function isPromotional(): ?bool
    {
        return $this->isPromotional;
    }

    public function setIsPromotional(?bool $isPromotional): static
    {
        $this->isPromotional = $isPromotional;

        return $this;
    }

    public function getDeviceIp(): ?string
    {
        return $this->deviceIp;
    }

    public function setDeviceIp(?string $deviceIp): static
    {
        $this->deviceIp = $deviceIp;
        return $this;
    }

    public function getUser(): ?AppUser
    {
        return $this->user;
    }

    public function setUser(?AppUser $user): static
    {
        $this->user = $user;

        return $this;
    }
}
