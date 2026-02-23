<?php

namespace App\Entity;

use App\Repository\ProductTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductTypeRepository::class)]
class ProductType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'productTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $slug = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'productType', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\Column]
    private array $defaultVariants = [];

    #[ORM\Column]
    private array $pricing = [];

    #[ORM\Column]
    private array $shipping = [];

    #[ORM\Column]
    private array $framePricing = [];

    #[ORM\Column]
    private array $customPricing = [];

    #[ORM\Column]
    private array $customVariants = [];

    #[ORM\Column]
    private array $seoMetaData = [];

    #[ORM\Column]
    private ?bool $customizable = null;

    #[ORM\Column]
    private ?bool $allowCustomSize = null;

    #[ORM\Column(nullable: true)]
    private ?array $variantMetaData = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $beforeLoginEspType = null;

    #[ORM\Column(nullable: true)]
    private ?float $beforeLoginEspPercentage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $afterLoginEspType = null;

    #[ORM\Column(nullable: true)]
    private ?float $afterLoginEspPercentage = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->products = new ArrayCollection();
        $this->defaultVariants = [];
        $this->pricing = [];
        $this->shipping = [];
        $this->framePricing = [];
        $this->customPricing = [];
        $this->customVariants = [];
        $this->seoMetaData = [];
        $this->customizable = false;
        $this->allowCustomSize = false;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setProductType($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProductType() === $this) {
                $product->setProductType(null);
            }
        }

        return $this;
    }

    public function getDefaultVariants(): array
    {
        return $this->defaultVariants;
    }

    public function setDefaultVariants(array $defaultVariants): static
    {
        $this->defaultVariants = $defaultVariants;

        return $this;
    }

    public function getPricing(): array
    {
        return $this->pricing;
    }

    public function setPricing(?array $pricing): static
    {
        $this->pricing = $pricing;

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

    public function getFramePricing(): array
    {
        return $this->framePricing;
    }

    public function setFramePricing(array $framePricing): static
    {
        $this->framePricing = $framePricing;

        return $this;
    }

    public function getCustomPricing(): array
    {
        return $this->customPricing;
    }

    public function setCustomPricing(array $customPricing): static
    {
        $this->customPricing = $customPricing;

        return $this;
    }

    public function getPricingAll(): array
    {
        return array_merge($this->pricing, $this->customPricing);
    }

    public function getCustomVariants(): array
    {
        return $this->customVariants;
    }

    public function setCustomVariants(array $customVariants): static
    {
        $this->customVariants = $customVariants;

        return $this;
    }

    public function getAllVariants(): array
    {
        return array_merge($this->defaultVariants, $this->customVariants);
    }

    public function getSeoMetaData(): array
    {
        return $this->seoMetaData;
    }

    public function setSeoMetaData(array $seoMetaData): static
    {
        $this->seoMetaData = $seoMetaData;

        return $this;
    }

    public function isCustomizable(): ?bool
    {
        return $this->customizable;
    }

    public function setIsCustomizable(bool $customizable): static
    {
        $this->customizable = $customizable;

        return $this;
    }

    public function isAllowCustomSize(): ?bool
    {
        return $this->allowCustomSize;
    }

    public function setAllowCustomSize(bool $allowCustomSize): static
    {
        $this->allowCustomSize = $allowCustomSize;

        return $this;
    }

    public function getVariantMetaData(): ?array
    {
        return $this->variantMetaData;
    }

    public function setVariantMetaData(?array $variantMetaData): static
    {
        $this->variantMetaData = $variantMetaData;

        return $this;
    }

    public function getBeforeLoginEspType(): ?string
    {
        return $this->beforeLoginEspType;
    }

    public function setBeforeLoginEspType(?string $beforeLoginEspType): static
    {
        $this->beforeLoginEspType = $beforeLoginEspType;

        return $this;
    }

    public function getBeforeLoginEspPercentage(): ?float
    {
        return $this->beforeLoginEspPercentage;
    }

    public function setBeforeLoginEspPercentage(?float $beforeLoginEspPercentage): static
    {
        $this->beforeLoginEspPercentage = $beforeLoginEspPercentage;

        return $this;
    }

    public function getAfterLoginEspType(): ?string
    {
        return $this->afterLoginEspType;
    }

    public function setAfterLoginEspType(?string $afterLoginEspType): static
    {
        $this->afterLoginEspType = $afterLoginEspType;

        return $this;
    }

    public function getAfterLoginEspPercentage(): ?float
    {
        return $this->afterLoginEspPercentage;
    }

    public function setAfterLoginEspPercentage(?float $afterLoginEspPercentage): static
    {
        $this->afterLoginEspPercentage = $afterLoginEspPercentage;

        return $this;
    }
}
