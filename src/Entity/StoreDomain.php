<?php

namespace App\Entity;

use App\Repository\StoreDomainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoreDomainRepository::class)]
class StoreDomain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeDomains')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $domain = null;


    #[ORM\Column]
    private ?bool $isEnabled = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'storeDomains')]
    #[ORM\JoinColumn]
    private ?Currency $currency = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'StoreDomain')]
    private Collection $orders;

    /**
     * @var Collection<int, ContactUs>
     */
    #[ORM\OneToMany(targetEntity: ContactUs::class, mappedBy: 'storeDomain')]
    private Collection $contactUs;

    /**
     * @var Collection<int, BulkOrder>
     */
    #[ORM\OneToMany(targetEntity: BulkOrder::class, mappedBy: 'storeDomain')]
    private Collection $bulkOrders;

    /**
     * @var Collection<int, CustomerPhotos>
     */
    #[ORM\OneToMany(targetEntity: CustomerPhotos::class, mappedBy: 'storeDomain')]
    private Collection $customerPhotos;

    /**
     * @var Collection<int, Subscriber>
     */
    #[ORM\OneToMany(targetEntity: Subscriber::class, mappedBy: 'storeDomain')]
    private Collection $subscribers;

    /**
     * @var Collection<int, SavedDesign>
     */
    #[ORM\OneToMany(targetEntity: SavedDesign::class, mappedBy: 'storeDomain')]
    private Collection $savedDesigns;

    /**
     * @var Collection<int, SavedCart>
     */
    #[ORM\OneToMany(targetEntity: SavedCart::class, mappedBy: 'storeDomain')]
    private Collection $savedCarts;

    /**
     * @var Collection<int, EmailQuote>
     */
    #[ORM\OneToMany(targetEntity: EmailQuote::class, mappedBy: 'storeDomain')]
    private Collection $emailQuotes;

    /**
     * @var Collection<int, WholeSeller>
     */
    #[ORM\OneToMany(targetEntity: WholeSeller::class, mappedBy: 'storeDomain')]
    private Collection $wholeSellers;

    /**
     * @var Collection<int, Distributor>
     */
    #[ORM\OneToMany(targetEntity: Distributor::class, mappedBy: 'storeDomain')]
    private Collection $distributors;


    public function __construct()
    {
        $this->isEnabled = true;
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
        $this->contactUs = new ArrayCollection();
        $this->bulkOrders = new ArrayCollection();
        $this->customerPhotos = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->savedDesigns = new ArrayCollection();
        $this->savedCarts = new ArrayCollection();
        $this->emailQuotes = new ArrayCollection();
        $this->wholeSellers = new ArrayCollection();
        $this->distributors = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

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
            $order->setStoreDomain($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getStoreDomain() === $this) {
                $order->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactUs>
     */
    public function getContactUs(): Collection
    {
        return $this->contactUs;
    }

    public function addContactUs(ContactUs $contactUs): static
    {
        if (!$this->contactUs->contains($contactUs)) {
            $this->contactUs->add($contactUs);
            $contactUs->setStoreDomain($this);
        }

        return $this;
    }

    public function removeContactUs(ContactUs $contactUs): static
    {
        if ($this->contactUs->removeElement($contactUs)) {
            // set the owning side to null (unless already changed)
            if ($contactUs->getStoreDomain() === $this) {
                $contactUs->setStoreDomain(null);
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
            $bulkOrder->setStoreDomain($this);
        }

        return $this;
    }

    public function removeBulkOrder(BulkOrder $bulkOrder): static
    {
        if ($this->bulkOrders->removeElement($bulkOrder)) {
            // set the owning side to null (unless already changed)
            if ($bulkOrder->getStoreDomain() === $this) {
                $bulkOrder->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomerPhotos>
     */
    public function getCustomerPhotos(): Collection
    {
        return $this->customerPhotos;
    }

    public function addCustomerPhoto(CustomerPhotos $customerPhoto): static
    {
        if (!$this->customerPhotos->contains($customerPhoto)) {
            $this->customerPhotos->add($customerPhoto);
            $customerPhoto->setStoreDomain($this);
        }

        return $this;
    }

    public function removeCustomerPhoto(CustomerPhotos $customerPhoto): static
    {
        if ($this->customerPhotos->removeElement($customerPhoto)) {
            // set the owning side to null (unless already changed)
            if ($customerPhoto->getStoreDomain() === $this) {
                $customerPhoto->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscriber>
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(Subscriber $subscriber): static
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers->add($subscriber);
            $subscriber->setStoreDomain($this);
        }

        return $this;
    }

    public function removeSubscriber(Subscriber $subscriber): static
    {
        if ($this->subscribers->removeElement($subscriber)) {
            // set the owning side to null (unless already changed)
            if ($subscriber->getStoreDomain() === $this) {
                $subscriber->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SavedDesign>
     */
    public function getSavedDesigns(): Collection
    {
        return $this->savedDesigns;
    }

    public function addSavedDesign(SavedDesign $savedDesign): static
    {
        if (!$this->savedDesigns->contains($savedDesign)) {
            $this->savedDesigns->add($savedDesign);
            $savedDesign->setStoreDomain($this);
        }

        return $this;
    }

    public function removeSavedDesign(SavedDesign $savedDesign): static
    {
        if ($this->savedDesigns->removeElement($savedDesign)) {
            // set the owning side to null (unless already changed)
            if ($savedDesign->getStoreDomain() === $this) {
                $savedDesign->setStoreDomain(null);
            }
        }

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
            $savedCart->setStoreDomain($this);
        }

        return $this;
    }

    public function removeSavedCart(SavedCart $savedCart): static
    {
        if ($this->savedCarts->removeElement($savedCart)) {
            // set the owning side to null (unless already changed)
            if ($savedCart->getStoreDomain() === $this) {
                $savedCart->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmailQuote>
     */
    public function getEmailQuotes(): Collection
    {
        return $this->emailQuotes;
    }

    public function addEmailQuote(EmailQuote $emailQuote): static
    {
        if (!$this->emailQuotes->contains($emailQuote)) {
            $this->emailQuotes->add($emailQuote);
            $emailQuote->setStoreDomain($this);
        }

        return $this;
    }

    public function removeEmailQuote(EmailQuote $emailQuote): static
    {
        if ($this->emailQuotes->removeElement($emailQuote)) {
            // set the owning side to null (unless already changed)
            if ($emailQuote->getStoreDomain() === $this) {
                $emailQuote->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WholeSeller>
     */
    public function getWholeSellers(): Collection
    {
        return $this->wholeSellers;
    }

    public function addWholeSeller(WholeSeller $wholeSeller): static
    {
        if (!$this->wholeSellers->contains($wholeSeller)) {
            $this->wholeSellers->add($wholeSeller);
            $wholeSeller->setStoreDomain($this);
        }

        return $this;
    }

    public function removeWholeSeller(WholeSeller $wholeSeller): static
    {
        if ($this->wholeSellers->removeElement($wholeSeller)) {
            // set the owning side to null (unless already changed)
            if ($wholeSeller->getStoreDomain() === $this) {
                $wholeSeller->setStoreDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Distributor>
     */
    public function getDistributors(): Collection
    {
        return $this->distributors;
    }

    public function addDistributor(Distributor $distributor): static
    {
        if (!$this->distributors->contains($distributor)) {
            $this->distributors->add($distributor);
            $distributor->setStoreDomain($this);
        }

        return $this;
    }

    public function removeDistributor(Distributor $distributor): static
    {
        if ($this->distributors->removeElement($distributor)) {
            if ($distributor->getStoreDomain() === $this) {
                $distributor->setStoreDomain(null);
            }
        }

        return $this;
    }

}
