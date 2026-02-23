<?php

namespace App\Entity;

use App\Entity\Admin\Coupon;
use App\Entity\Blog\Post;
use App\Entity\Subscriber;
use App\Repository\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoreRepository::class)]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    private ?string $shortName = null;

    #[ORM\Column]
    private ?bool $isEnabled = null;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: StoreDomain::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $storeDomains;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Category::class, orphanRemoval: true)]
    private Collection $categories;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: ProductType::class)]
    private Collection $productTypes;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Subscriber::class)]
    private Collection $contactEnquiries;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Coupon::class)]
    private Collection $coupon;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: CommunityUploads::class)]
    private Collection $communityUploads;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Post::class)]
    private Collection $posts;

    /**
     * @var Collection<int, StoreSettings>
     */
    #[ORM\OneToMany(targetEntity: StoreSettings::class, mappedBy: 'store')]
    private Collection $storeSettings;

    /**
     * @var Collection<int, BulkOrder>
     */
    #[ORM\OneToMany(targetEntity: BulkOrder::class, mappedBy: 'store')]
    private Collection $bulkOrders;

    public function __construct()
    {
        $this->storeDomains = new ArrayCollection();
        $this->isEnabled = true;
        $this->categories = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->productTypes = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->contactEnquiries = new ArrayCollection();
        $this->coupon = new ArrayCollection();
        $this->communityUploads = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->storeSettings = new ArrayCollection();
        $this->bulkOrders = new ArrayCollection();
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

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): static
    {
        $this->shortName = $shortName;

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

    /**
     * @return Collection<int, StoreDomain>
     */
    public function getStoreDomains(): Collection
    {
        return $this->storeDomains;
    }

    public function addStoreDomain(StoreDomain $storeDomain): static
    {
        if (!$this->storeDomains->contains($storeDomain)) {
            $this->storeDomains->add($storeDomain);
            $storeDomain->setStore($this);
        }

        return $this;
    }

    public function removeStoreDomain(StoreDomain $storeDomain): static
    {
        if ($this->storeDomains->removeElement($storeDomain)) {
            // set the owning side to null (unless already changed)
            if ($storeDomain->getStore() === $this) {
                $storeDomain->setStore(null);
            }
        }

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
            $category->setStore($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getStore() === $this) {
                $category->setStore(null);
            }
        }

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
            $product->setStore($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getStore() === $this) {
                $product->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductType>
     */
    public function getProductTypes(): Collection
    {
        return $this->productTypes;
    }

    public function addProductType(ProductType $productType): static
    {
        if (!$this->productTypes->contains($productType)) {
            $this->productTypes->add($productType);
            $productType->setStore($this);
        }

        return $this;
    }

    public function removeProductType(ProductType $productType): static
    {
        if ($this->productTypes->removeElement($productType)) {
            // set the owning side to null (unless already changed)
            if ($productType->getStore() === $this) {
                $productType->setStore(null);
            }
        }

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
            $order->setStore($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getStore() === $this) {
                $order->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscriber>
     */
    public function getContactEnquiries(): Collection
    {
        return $this->contactEnquiries;
    }

    public function addSubscriber(Subscriber $subscriber): static
    {
        if (!$this->contactEnquiries->contains($subscriber)) {
            $this->contactEnquiries->add($subscriber);
            $subscriber->setStore($this);
        }

        return $this;
    }

    public function removeSubscriber(Subscriber $subscriber): static
    {
        if ($this->contactEnquiries->removeElement($subscriber)) {
            // set the owning side to null (unless already changed)
            if ($subscriber->getStore() === $this) {
                $subscriber->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Coupon>
     */
    public function getCoupon(): Collection
    {
        return $this->coupon;
    }

    public function addCoupon(Coupon $coupon): static
    {
        if (!$this->coupon->contains($coupon)) {
            $this->coupon->add($coupon);
            $coupon->setStore($this);
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): static
    {
        if ($this->coupon->removeElement($coupon)) {
            // set the owning side to null (unless already changed)
            if ($coupon->getStore() === $this) {
                $coupon->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommunityUploads>
     */
    public function getCommunityUploads(): Collection
    {
        return $this->communityUploads;
    }

    public function addCommunityUpload(CommunityUploads $communityUpload): static
    {
        if (!$this->communityUploads->contains($communityUpload)) {
            $this->communityUploads->add($communityUpload);
            $communityUpload->setStore($this);
        }

        return $this;
    }

    public function removeCommunityUpload(CommunityUploads $communityUpload): static
    {
        if ($this->communityUploads->removeElement($communityUpload)) {
            // set the owning side to null (unless already changed)
            if ($communityUpload->getStore() === $this) {
                $communityUpload->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setStore($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getStore() === $this) {
                $post->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StoreSettings>
     */
    public function getStoreSettings(): Collection
    {
        return $this->storeSettings;
    }

    public function addStoreSetting(StoreSettings $storeSetting): static
    {
        if (!$this->storeSettings->contains($storeSetting)) {
            $this->storeSettings->add($storeSetting);
            $storeSetting->setStore($this);
        }

        return $this;
    }

    public function removeStoreSetting(StoreSettings $storeSetting): static
    {
        if ($this->storeSettings->removeElement($storeSetting)) {
            // set the owning side to null (unless already changed)
            if ($storeSetting->getStore() === $this) {
                $storeSetting->setStore(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BulkOrder>
     */
    public function getBulkOrders(): Collection
    {
        return $this->bulkOrders;
    }

    public function addBulkOrder(BulkOrder $bulkOrder): static
    {
        if (!$this->bulkOrders->contains($bulkOrder)) {
            $this->bulkOrders->add($bulkOrder);
            $bulkOrder->setStore($this);
        }

        return $this;
    }

    public function removeBulkOrder(BulkOrder $bulkOrder): static
    {
        if ($this->bulkOrders->removeElement($bulkOrder)) {
            // set the owning side to null (unless already changed)
            if ($bulkOrder->getStore() === $this) {
                $bulkOrder->setStore(null);
            }
        }

        return $this;
    }
}
