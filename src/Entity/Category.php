<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[Vich\Uploadable]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'integer', length: 4)]
    private ?int $sortPosition = null;

    #[ORM\Column]
    private ?bool $isEnabled = null;

    #[ORM\Column(nullable: true)]
    private array $seoMeta = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $banner = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "banner.name",
        size: "banner.size",
        mimeType: "banner.mimeType",
        originalName: "banner.originalName",
        dimensions: "banner.dimensions"
    )]
    private ?File $bannerFile = null;
    
    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $promoBanner = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "promoBanner.name",
        size: "promoBanner.size",
        mimeType: "promoBanner.mimeType",
        originalName: "promoBanner.originalName",
        dimensions: "promoBanner.dimensions"
    )]
    private ?File $promoBannerFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $mobileBanner = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "mobileBanner.name",
        size: "mobileBanner.size",
        mimeType: "mobileBanner.mimeType",
        originalName: "mobileBanner.originalName",
        dimensions: "mobileBanner.dimensions"
    )]
    private ?File $mobileBannerFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $promoMobileBanner = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "promoMobileBanner.name",
        size: "promoMobileBanner.size",
        mimeType: "promoMobileBanner.mimeType",
        originalName: "promoMobileBanner.originalName",
        dimensions: "promoMobileBanner.dimensions"
    )]
    private ?File $promoMobileBannerFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $thumbnail = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "thumbnail.name",
        size: "thumbnail.size",
        mimeType: "thumbnail.mimeType",
        originalName: "thumbnail.originalName",
        dimensions: "thumbnail.dimensions"
    )]
    private ?File $thumbnailFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $promoThumbnail = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "promoThumbnail.name",
        size: "promoThumbnail.size",
        mimeType: "promoThumbnail.mimeType",
        originalName: "promoThumbnail.originalName",
        dimensions: "promoThumbnail.dimensions"
    )]
    private ?File $promoThumbnailFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $categoryThumbnail = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "categoryThumbnail.name",
        size: "categoryThumbnail.size",
        mimeType: "categoryThumbnail.mimeType",
        originalName: "categoryThumbnail.originalName",
        dimensions: "categoryThumbnail.dimensions"
    )]
    private ?File $categoryThumbnailFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $promoCategoryThumbnail = null;

    #[Vich\UploadableField(
        mapping: 'category',
        fileNameProperty: "promoCategoryThumbnail.name",
        size: "promoCategoryThumbnail.size",
        mimeType: "promoCategoryThumbnail.mimeType",
        originalName: "promoCategoryThumbnail.originalName",
        dimensions: "promoCategoryThumbnail.dimensions"
    )]
    private ?File $promoCategoryThumbnailFile = null;

    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'categories')]
    private Collection $products;

    #[ORM\OneToMany(mappedBy: 'primaryCategory', targetEntity: Product::class)]
    private Collection $primaryProducts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $skuInitial = null;

    #[ORM\Column(nullable: true)]
    private ?int $oldCategoryId = null;

    #[ORM\Column]
    private array $migratedData = [];

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryBlocks::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $categoryBlocks;

    #[ORM\Column]
    private ?bool $displayInMenu = null;

    #[Groups(['apiData'])]
    private ?string $thumbnailUrl = null;

    #[Groups(['apiData'])]
    private ?string $promoThumbnailUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $productSeoMeta = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayLayout = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subCategories')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $subCategories;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $productDescription = null;

    public const SIZE_SPECIFIC = 'SIZE_SPECIFIC';
    public const LIST_VIEW = 'LIST_VIEW';
    public const CATEGORY_VIEW = 'CATEGORY_VIEW';
    public const CATEGORY_SIZE_VIEW = 'CATEGORY_SIZE_VIEW';

    public const DISPLAY_LAYOUT = [
        self::SIZE_SPECIFIC => 'Size Specific',
        self::LIST_VIEW => 'List View',
        self::CATEGORY_VIEW => 'Category View',
        self::CATEGORY_SIZE_VIEW => 'Category Size View',
    ];

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->isEnabled = true;
        $this->banner = new EmbeddedFile();
        $this->promoBanner = new EmbeddedFile();
        $this->mobileBanner = new EmbeddedFile();
        $this->promoMobileBanner = new EmbeddedFile();
        $this->thumbnail = new EmbeddedFile();
        $this->promoThumbnail = new EmbeddedFile();
        $this->categoryThumbnail = new EmbeddedFile();
        $this->promoCategoryThumbnail = new EmbeddedFile();
        $this->products = new ArrayCollection();
        $this->primaryProducts = new ArrayCollection();
        $this->migratedData = [];
        $this->categoryBlocks = new ArrayCollection();
        $this->productSeoMeta = [];
        $this->subCategories = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
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

    public function getSortPosition(): ?string
    {
        return $this->sortPosition;
    }

    public function setSortPosition(?int $sortPosition): static
    {
        $this->sortPosition = $sortPosition;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getSeoMeta(): array
    {
        return $this->seoMeta;
    }

    public function setSeoMeta(array $seoMeta): static
    {
        $this->seoMeta = $seoMeta;

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

    public function getBanner(): ?EmbeddedFile
    {
        return $this->banner;
    }

    public function setBanner(EmbeddedFile $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function setBannerFile(?File $bannerFile = null): void
    {
        $this->bannerFile = $bannerFile;

        if (null !== $bannerFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getBannerFile(): ?File
    {
        return $this->bannerFile;
    }

    public function getPromoBanner(): ?EmbeddedFile
    {
        return $this->promoBanner;
    }

    public function setPromoBanner(EmbeddedFile $promoBanner): static
    {
        $this->promoBanner = $promoBanner;

        return $this;
    }

    public function setPromoBannerFile(?File $promoBannerFile = null): void
    {
        $this->promoBannerFile = $promoBannerFile;

        if (null !== $promoBannerFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPromoBannerFile(): ?File
    {
        return $this->promoBannerFile;
    }


    public function getMobileBanner(): ?EmbeddedFile
    {
        return $this->mobileBanner;
    }

    public function setMobileBanner(EmbeddedFile $mobileBanner): static
    {
        $this->mobileBanner = $mobileBanner;

        return $this;
    }

    public function setMobileBannerFile(?File $mobileBannerFile = null): void
    {
        $this->mobileBannerFile = $mobileBannerFile;

        if (null !== $mobileBannerFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getMobileBannerFile(): ?File
    {
        return $this->mobileBannerFile;
    }

    public function getPromoMobileBanner(): ?EmbeddedFile
    {
        return $this->promoMobileBanner;
    }

    public function setPromoMobileBanner(EmbeddedFile $promoMobileBanner): static
    {
        $this->promoMobileBanner = $promoMobileBanner;

        return $this;
    }

    public function setPromoMobileBannerFile(?File $promoMobileBannerFile = null): void
    {
        $this->promoMobileBannerFile = $promoMobileBannerFile;

        if (null !== $promoMobileBannerFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPromoMobileBannerFile(): ?File
    {
        return $this->promoMobileBannerFile;
    }

    public function getCategoryThumbnail(): ?EmbeddedFile
    {
        return $this->categoryThumbnail;
    }

    public function setCategoryThumbnail(EmbeddedFile $categoryThumbnail): static
    {
        $this->categoryThumbnail = $categoryThumbnail;

        return $this;
    }

    public function setCategoryThumbnailFile(?File $categoryThumbnailFile = null): void
    {
        $this->categoryThumbnailFile = $categoryThumbnailFile;

        if (null !== $categoryThumbnailFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getCategoryThumbnailFile(): ?File
    {
        return $this->categoryThumbnailFile;
    }

    public function getPromoCategoryThumbnail(): ?EmbeddedFile
    {
        return $this->promoCategoryThumbnail;
    }

    public function setPromoCategoryThumbnail(EmbeddedFile $promoCategoryThumbnail): static
    {
        $this->promoCategoryThumbnail = $promoCategoryThumbnail;

        return $this;
    }

    public function setPromoCategoryThumbnailFile(?File $promoCategoryThumbnailFile = null): void
    {
        $this->promoCategoryThumbnailFile = $promoCategoryThumbnailFile;

        if (null !== $promoCategoryThumbnailFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPromoCategoryThumbnailFile(): ?File
    {
        return $this->promoCategoryThumbnailFile;
    }

    public function getThumbnail(): ?EmbeddedFile
    {
        return $this->thumbnail;
    }

    public function setThumbnail(EmbeddedFile $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function setThumbnailFile(?File $thumbnailFile = null): void
    {
        $this->thumbnailFile = $thumbnailFile;

        if (null !== $thumbnailFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getThumbnailFile(): ?File
    {
        return $this->thumbnailFile;
    }

    public function getPromoThumbnail(): ?EmbeddedFile
    {
        return $this->promoThumbnail;
    }

    public function setPromoThumbnail(EmbeddedFile $promoThumbnail): static
    {
        $this->promoThumbnail = $promoThumbnail;

        return $this;
    }

    public function setPromoThumbnailFile(?File $promoThumbnailFile = null): void
    {
        $this->promoThumbnailFile = $promoThumbnailFile;

        if (null !== $promoThumbnailFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPromoThumbnailFile(): ?File
    {
        return $this->promoThumbnailFile;
    }


    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProductsAll(): Collection
    {
        $productsArray = $this->products->toArray();
        $primaryProductsArray = $this->primaryProducts->toArray();

        $mergedArray = $productsArray + $primaryProductsArray;

        return new ArrayCollection(array_values($mergedArray));
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            $product->removeCategory($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getPrimaryProducts(): Collection
    {
        return $this->primaryProducts;
    }

    public function addPrimaryProduct(Product $primaryProduct): static
    {
        if (!$this->primaryProducts->contains($primaryProduct)) {
            $this->primaryProducts->add($primaryProduct);
            $primaryProduct->setPrimaryCategory($this);
        }

        return $this;
    }

    public function removePrimaryProduct(Product $primaryProduct): static
    {
        if ($this->primaryProducts->removeElement($primaryProduct)) {
            // set the owning side to null (unless already changed)
            if ($primaryProduct->getPrimaryCategory() === $this) {
                $primaryProduct->setPrimaryCategory(null);
            }
        }

        return $this;
    }

    public function getSkuInitial(): ?string
    {
        return $this->skuInitial ?? 'YSP';
    }

    public function setSkuInitial(?string $skuInitial): static
    {
        $this->skuInitial = $skuInitial;

        return $this;
    }

    public function getOldCategoryId(): ?int
    {
        return $this->oldCategoryId;
    }

    public function setOldCategoryId(?int $oldCategoryId): static
    {
        $this->oldCategoryId = $oldCategoryId;

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

    /**
     * @return Collection<int, CategoryBlocks>
     */
    public function getCategoryBlocks(): Collection
    {
        return $this->categoryBlocks;
    }

    public function addCategoryBlock(CategoryBlocks $categoryBlock): static
    {
        if (!$this->categoryBlocks->contains($categoryBlock)) {
            $this->categoryBlocks->add($categoryBlock);
            $categoryBlock->setCategory($this);
        }

        return $this;
    }

    public function removeCategoryBlock(CategoryBlocks $categoryBlock): static
    {
        if ($this->categoryBlocks->removeElement($categoryBlock)) {
            // set the owning side to null (unless already changed)
            if ($categoryBlock->getCategory() === $this) {
                $categoryBlock->setCategory(null);
            }
        }

        return $this;
    }

    public function isDisplayInMenu(): ?bool
    {
        return $this->displayInMenu;
    }

    public function setDisplayInMenu(bool $displayInMenu): static
    {
        $this->displayInMenu = $displayInMenu;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return 'https://static.yardsignplus.com/fit-in/350x350/category/'.$this->thumbnail->getName();
    }

    public function getPromoThumbnailUrl(): ?string
    {
        return 'https://static.yardsignplus.com/fit-in/350x350/category/'.$this->promoThumbnail->getName();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getProductSeoMeta(): ?array
    {
        return $this->productSeoMeta;
    }

    public function setProductSeoMeta(?array $productSeoMeta): static
    {
        $this->productSeoMeta = $productSeoMeta;

        return $this;
    }

    public function getDisplayLayout(): ?string
    {
        return $this->displayLayout;
    }

    public function setDisplayLayout(?string $displayLayout): static
    {
        $this->displayLayout = $displayLayout;

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
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(self $category): static
    {
        if (!$this->subCategories->contains($category)) {
            $this->subCategories->add($category);
            $category->setParent($this);
        }

        return $this;
    }

    public function removeSubCategory(self $category): static
    {
        if ($this->subCategories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getParent() === $this) {
                $category->setParent(null);
            }
        }

        return $this;
    }

    public function getProductDescription(): ?string
    {
        return $this->productDescription;
    }

    public function setProductDescription(?string $productDescription): static
    {
        $this->productDescription = $productDescription;

        return $this;
    }

}
