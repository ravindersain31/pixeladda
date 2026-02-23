<?php

namespace App\Entity;

use App\Helper\PriceChartHelper;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;
use Doctrine\Common\Collections\Order as OrderCollection;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[Vich\Uploadable]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['apiData'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $label = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modalName = null;

    #[ORM\Column(type: 'text')]
    private ?string $slug = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['apiData'])]
    private ?string $sku = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Store $store = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['apiData'])]
    private ?ProductType $productType = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    private Collection $categories;

    #[ORM\Column(nullable: true)]
    private array $seoMeta = [];

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $image = null;

    #[Vich\UploadableField(
        mapping: 'product_image',
        fileNameProperty: "image.name",
        size: "image.size",
        mimeType: "image.mimeType",
        originalName: "image.originalName",
        dimensions: "image.dimensions"
    )]
    private ?File $imageFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $promoImage = null;

    #[Vich\UploadableField(
        mapping: 'product_image',
        fileNameProperty: "promoImage.name",
        size: "promoImage.size",
        mimeType: "promoImage.mimeType",
        originalName: "promoImage.originalName",
        dimensions: "promoImage.dimensions"
    )]
    private ?File $promoImageFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $seoImage = null;

    #[Vich\UploadableField(
        mapping: 'product_image',
        fileNameProperty: "seoImage.name",
        size: "seoImage.size",
        mimeType: "seoImage.mimeType",
        originalName: "seoImage.originalName",
        dimensions: "seoImage.dimensions"
    )]
    private ?File $seoImageFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $displayImage = null;

    #[Vich\UploadableField(
        mapping: 'product_image',
        fileNameProperty: "displayImage.name",
        size: "displayImage.size",
        mimeType: "displayImage.mimeType",
        originalName: "displayImage.originalName",
        dimensions: "displayImage.dimensions"
    )]
    private ?File $displayImageFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $template = null;

    #[Vich\UploadableField(
        mapping: 'product_image',
        fileNameProperty: "template.name",
        size: "template.size",
        mimeType: "template.mimeType",
        originalName: "template.originalName",
        dimensions: "template.dimensions"
    )]
    private ?File $templateFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $promoTemplate = null;

    #[Vich\UploadableField(
        mapping: 'product_image',
        fileNameProperty: "promoTemplate.name",
        size: "promoTemplate.size",
        mimeType: "promoTemplate.mimeType",
        originalName: "promoTemplate.originalName",
        dimensions: "promoTemplate.dimensions"
    )]
    private ?File $promoTemplateFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $hasVariant = null;

    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['persist'], inversedBy: 'variants')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[Groups(['apiData'])]
    private Collection $variants;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column]
    private array $pricing = [];

    #[ORM\ManyToOne(inversedBy: 'primaryProducts')]
    #[Groups(['apiData'])]
    private ?Category $primaryCategory = null;

    #[ORM\Column]
    private array $migratedData = [];

    #[ORM\Column]
    private ?bool $isEnabled = null;

    #[ORM\Column]
    private array $metaData = [];

    #[Groups(['apiData'])]
    private ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $sortPosition = null;

    #[Groups(['apiData'])]
    private ?string $lowestPrice = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCustomSize = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['apiData'])]
    private Collection $productImages;

    #[ORM\Column(nullable: true)]
    private ?array $productMetaData = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $templateLabel = null;

    public const VARIANTS = [
        '24x18' => 1,
        '18x12' => 2,
        '18x24' => 3,
        '24x24' => 4,
        '12x18' => 5,
        '12x12' => 6,
        '9x24' => 7,
        '9x12' => 8,
        '6x24' => 9,
        '6x18' => 10,
    ];

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
        $this->image = new EmbeddedFile();
        $this->promoImage = new EmbeddedFile();
        $this->seoImage = new EmbeddedFile();
        $this->displayImage = new EmbeddedFile();
        $this->template = new EmbeddedFile();
        $this->promoTemplate = new EmbeddedFile();
        $this->hasVariant = false;
        $this->variants = new ArrayCollection();
        $this->pricing = [];
        $this->migratedData = [];
        $this->productImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getModalName(): ?string
    {
        return $this->modalName;
    }

    public function setModalName(string $modalName): static
    {
        $this->modalName = $modalName;

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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

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

    public function getProductType(): ?ProductType
    {
        return $this->productType;
    }

    public function setProductType(?ProductType $productType): static
    {
        $this->productType = $productType;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getSeoMeta(): array
    {
        return $this->seoMeta;
    }

    public function setSeoMeta(?array $seoMeta): static
    {
        $this->seoMeta = $seoMeta;

        return $this;
    }

    public function getImage(): ?EmbeddedFile
    {
        return $this->image;
    }

    public function setImage(EmbeddedFile $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getPromoImage(): ?EmbeddedFile
    {
        return $this->promoImage;
    }

    public function setPromoImage(EmbeddedFile $promoImage): static
    {
        $this->promoImage = $promoImage;

        return $this;
    }

    #[Groups(['apiData'])]
    public function getPromoImageUrl(): ?string
    {
        return 'https://static.yardsignplus.com/product/img/' . $this->promoImage->getName();
    }

    public function setPromoImageFile(?File $promoImageFile = null): void
    {
        $this->promoImageFile = $promoImageFile;

        if (null !== $promoImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPromoImageFile(): ?File
    {
        return $this->promoImageFile;
    }

    public function getSeoImage(): ?EmbeddedFile
    {
        return $this->seoImage;
    }

    public function setSeoImage(EmbeddedFile $seoImage): static
    {
        $this->seoImage = $seoImage;

        return $this;
    }

    public function setSeoImageFile(?File $seoImageFile = null): void
    {
        $this->seoImageFile = $seoImageFile;

        if (null !== $seoImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getSeoImageFile(): ?File
    {
        return $this->seoImageFile;
    }

    public function getDisplayImage(): ?EmbeddedFile
    {
        return $this->displayImage;
    }

    public function setDisplayImage(EmbeddedFile $displayImage): static
    {
        $this->displayImage = $displayImage;

        return $this;
    }

    public function setDisplayImageFile(?File $displayImageFile = null): void
    {
        $this->displayImageFile = $displayImageFile;

        if (null !== $displayImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getDisplayImageFile(): ?File
    {
        return $this->displayImageFile;
    }

    public function getTemplate(): ?EmbeddedFile
    {
        return $this->template;
    }

    public function setTemplate(EmbeddedFile $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function setTemplateFile(?File $templateFile = null): void
    {
        $this->templateFile = $templateFile;

        if (null !== $templateFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getTemplateFile(): ?File
    {
        return $this->templateFile;
    }

    public function getPromoTemplate(): ?EmbeddedFile
    {
        return $this->promoTemplate;
    }

    public function setPromoTemplate(EmbeddedFile $promoTemplate): static
    {
        $this->promoTemplate = $promoTemplate;

        return $this;
    }

    public function setPromoTemplateFile(?File $promoTemplateFile = null): void
    {
        $this->promoTemplateFile = $promoTemplateFile;

        if (null !== $promoTemplateFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPromoTemplateFile(): ?File
    {
        return $this->promoTemplateFile;
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

    public function isHasVariant(): ?bool
    {
        return $this->hasVariant;
    }

    public function setHasVariant(bool $hasVariant): static
    {
        $this->hasVariant = $hasVariant;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getVariants(): Collection
    {
        $criteria = Criteria::create()->orderBy(['sortPosition' => OrderCollection::Ascending]);
        return $this->variants->filter(fn(self $variant) => null === $variant->getDeletedAt())->matching($criteria);
    }

    public function addVariant(self $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setParent($this);
            $variant->setDeletedAt(null);
        }

        return $this;
    }

    public function removeVariant(self $variant): static
    {
        if ($this->variants->removeElement($variant)) {
            // set the owning side to null (unless already changed)
            if ($variant->getParent() === $this) {
                // $variant->setParent(null);
                $variant->setDeletedAt(new \DateTimeImmutable());
            }
        }

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getPricing(): array
    {
        return $this->pricing;
    }

    public function setPricing(array $pricing): static
    {
        $this->pricing = $pricing;

        return $this;
    }

    public function getPrimaryCategory(): ?Category
    {
        return $this->primaryCategory;
    }

    public function setPrimaryCategory(?Category $primaryCategory): static
    {
        $this->primaryCategory = $primaryCategory;

        return $this;
    }

    public function getMigratedData(): array
    {
        return $this->migratedData;
    }

    public function setMigratedData(array $migratedData): static
    {
        $this->migratedData = $migratedData;

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

    public function getImageUrl(): ?string
    {
        return 'https://static.yardsignplus.com/product/img/' . $this->image->getName();
    }

    public function lowestPrice(string $currency = 'USD', ?string $variant = null, ?User $user = null): float
    {
        $pricing = $this->getPricing();
        $espProductType = null;
        if (count($pricing) <= 0 && $this->getParent() !== null) {
            $pricing = $this->getParent()->getPricing();
        }
        if (count($pricing) <= 0) {
            if ($this->getProductType()) {
                $pricing = $this->getProductType()->getPricingAll();
                $espProductType = $this->getProductType();
            } else {
                $pricing = $this->getParent()->getProductType()->getPricingAll();
                $espProductType = $this->getProductType();
            }
        }

        $pricing = PriceChartHelper::getHostBasedPrice($pricing, $espProductType, $user);
        return PriceChartHelper::getLowestPrice($pricing, $currency, $variant);
    }

    public function highestPrice(string $currency = 'USD', ?string $variant = null, ?User $user = null): float
    {
        $pricing = $this->getPricing();
        if (count($pricing) <= 0 && $this->getParent() !== null) {
            $pricing = $this->getParent()->getPricing();
        }
         if (count($pricing) <= 0) {
            if ($this->getProductType()) {
                $pricing = $this->getProductType()->getPricingAll();
                $espProductType = $this->getProductType();
            } else {
                $pricing = $this->getParent()->getProductType()->getPricingAll();
                $espProductType = $this->getProductType();
            }
        }

        $pricing = PriceChartHelper::getHostBasedPrice($pricing, $espProductType, $user);
        return PriceChartHelper::getHighestPrice($pricing, $currency, $variant);
    }

    #[Groups(['apiData'])]
    public function getProductTypePricing(?User $user = null): array
    {
        $pricing = $this->getPricing();
        $espProductType = null;
        if (count($pricing) <= 0 && $this->getParent() !== null) {
            $pricing = $this->getParent()->getPricing();
        }
        if (count($pricing) <= 0) {
            if ($this->getProductType()) {
                $pricing = $this->getProductType()->getPricing();
                $espProductType = $this->getProductType();
            } else {
                $pricing = $this->getParent()->getProductType()->getPricing();
                $espProductType = $this->getProductType();
            }
        }

        $pricingAll = $pricing ?? [];

        if ($this->getProductType() && $this->getProductType()->getCustomPricing()) {
            $customPricing = $this->getProductType()->getCustomPricing() ?? [];
            $pricingAll = array_merge($pricing, $customPricing);
        }

        $pricingAll = PriceChartHelper::getHostBasedPrice($pricingAll, $espProductType, $user);
        return $pricingAll;
    }

    public function getReviewCount(): string
    {
        return (string) rand(30, 150);
    }

    public function getSortPosition(): ?int
    {
        return $this->sortPosition;
    }

    public function setSortPosition(?int $sortPosition): static
    {
        $this->sortPosition = $sortPosition;

        return $this;
    }

    public function isIsCustomSize(): ?bool
    {
        return $this->isCustomSize;
    }

    public function setisCustomSize(bool $isCustomSize): static
    {
        $this->isCustomSize = $isCustomSize;

        return $this;
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getProductImages(): Collection
    {
        $criteria = Criteria::create()->orderBy(['sortPosition' => OrderCollection::Ascending]);
        return $this->productImages->matching($criteria);
    }

    public function addProductImage(ProductImage $productImage): static
    {
        if (!$this->productImages->contains($productImage)) {
            $this->productImages->add($productImage);
            $productImage->setProduct($this);
        }

        return $this;
    }

    public function removeProductImage(ProductImage $productImage): static
    {
        if ($this->productImages->removeElement($productImage)) {
            // set the owning side to null (unless already changed)
            if ($productImage->getProduct() === $this) {
                $productImage->setProduct(null);
            }
        }

        return $this;
    }

    public function getProductMetaData(): ?array
    {
        return $this->productMetaData;
    }

    public function getProductMetaDataKey(string $key)
    {
        if (isset($this->productMetaData[$key])) {
            return $this->productMetaData[$key];
        }
        return null;
    }

    public function setProductMetaData(?array $productMetaData): self
    {
        $this->productMetaData = $productMetaData;

        return $this;
    }

    public function setProductMetaDataKey(string $key, $value): self
    {
        $productMetaData = $this->productMetaData;
        $productMetaData[$key] = $value;
        $this->productMetaData = $productMetaData;
        return $this;
    }

    public function getTemplateLabel(): ?string
    {
        return $this->templateLabel;
    }

    public function setTemplateLabel(?string $templateLabel): static
    {
        $this->templateLabel = $templateLabel;

        return $this;
    }
}
