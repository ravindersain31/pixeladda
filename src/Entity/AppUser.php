<?php

namespace App\Entity;

use App\Entity\Admin\Coupon;
use App\Entity\Reward\Reward;
use App\Repository\AppUserRepository;
use App\Service\Reward\RewardService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


#[ORM\Entity(repositoryClass: AppUserRepository::class)]
#[Vich\Uploadable]
class AppUser extends User
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    #[Assert\Email(message:'The email must be valid email address')]
    protected ?string $email;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mobile = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $migratedData = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $braintreeId = null;

    #[ORM\OneToOne(inversedBy: 'appUser', targetEntity: Reward::class, cascade: ['persist', 'remove'])]
    private ?Reward $reward = null;

    #[ORM\OneToMany(mappedBy: 'referrer', targetEntity: Referral::class)]
    private Collection $referrals;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referralCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;


    #[ORM\OneToOne(targetEntity: UserFile::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'whole_seller_image_file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UserFile $wholeSellerImageFile = null;

    #[ORM\OneToOne(mappedBy: 'appUser', cascade: ['persist', 'remove'])]
    private ?WholeSeller $wholeSeller = null;

    /**
     * @var Collection<int, Coupon>
     */
    #[ORM\OneToMany(targetEntity: Coupon::class, mappedBy: 'user')]
    private Collection $coupons;

    /**
     * @var Collection<int, SavedPaymentDetail>
     */
    #[ORM\OneToMany(targetEntity: SavedPaymentDetail::class, mappedBy: 'user')]
    private Collection $savedPaymentDetails;

    public function __construct()
    {
        parent::__construct();
        $this->orders = new ArrayCollection();
        $this->referrals = new ArrayCollection();
        $this->coupons = new ArrayCollection();
        $this->savedPaymentDetails = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): static
    {
        $this->mobile = $mobile;

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
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getMigratedData(): array
    {
        return $this->migratedData;
    }

    public function setMigratedData(?array $migratedData): static
    {
        $this->migratedData = $migratedData;

        return $this;
    }

    public function getBraintreeId(): ?string
    {
        return $this->braintreeId;
    }

    public function setBraintreeId(?string $braintreeId): static
    {
        $this->braintreeId = $braintreeId;

        return $this;
    }

    public function getReward(): ?Reward
    {
        if(!$this->reward){
            $this->reward = new Reward();
            $this->reward->setUser($this);
        }
        $this->reward?->recalculatePoints();
        return $this->reward;
    }

    public function setReward(?Reward $reward): static
    {
        $this->reward = $reward;

        return $this;
    }

    /**
     * @return Collection<int, Referral>
     */
    public function getReferrals(): Collection
    {
        return $this->referrals;
    }

    public function addReferral(Referral $referral): static
    {
        if (!$this->referrals->contains($referral)) {
            $this->referrals->add($referral);
            $referral->setReferrer($this);
        }

        return $this;
    }

    public function removeReferral(Referral $referral): static
    {
        if ($this->referrals->removeElement($referral)) {
            // set the owning side to null (unless already changed)
            if ($referral->getReferrer() === $this) {
                $referral->setReferrer(null);
            }
        }

        return $this;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function setReferralCode(?string $referralCode): static
    {
        $this->referralCode = $referralCode;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }


    public function getWholeSellerImageFile(): ?UserFile
    {
        return $this->wholeSellerImageFile;
    }

    public function setWholeSellerImageFile(?UserFile $file): self
    {
        $this->wholeSellerImageFile = $file;
        return $this;
    }

    public function getWholeSellerFile(string $type = 'pdf'): ?UserFile
    {
        $file = $this->wholeSellerImageFile;

        if (!$file) {
            return null;
        }

        return ($type === 'pdf' && $file->getType() === 'PROOF_FILE')
            || ($type === 'image' && $file->getType() === 'PROOF_IMAGE')
            ? $file
            : null;
    }

    public function getWholeSeller(): ?WholeSeller
    {
        return $this->wholeSeller;
    }

    public function setWholeSeller(?WholeSeller $wholeSeller): static
    {
        // unset the owning side of the relation if necessary
        if ($wholeSeller === null && $this->wholeSeller !== null) {
            $this->wholeSeller->setAppUser(null);
        }

        // set the owning side of the relation if necessary
        if ($wholeSeller !== null && $wholeSeller->getAppUser() !== $this) {
            $wholeSeller->setAppUser($this);
        }

        $this->wholeSeller = $wholeSeller;

        return $this;
    }


    /**
     * @return Collection<int, Coupon>
     */
    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function addCoupon(Coupon $coupon): static
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons->add($coupon);
            $coupon->setUser($this);
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): static
    {
        if ($this->coupons->removeElement($coupon)) {
            if ($coupon->getUser() === $this) {
                $coupon->setUser(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection<int, SavedPaymentDetail>
     */
    public function getSavedPaymentDetails(): Collection
    {
        return $this->savedPaymentDetails;
    }

    public function addSavedPaymentDetail(SavedPaymentDetail $savedPaymentDetail): static
    {
        if (!$this->savedPaymentDetails->contains($savedPaymentDetail)) {
            $this->savedPaymentDetails->add($savedPaymentDetail);
            $savedPaymentDetail->setUser($this);
        }

        return $this;
    }

    public function removeSavedPaymentDetail(SavedPaymentDetail $savedPaymentDetail): static
    {
        if ($this->savedPaymentDetails->removeElement($savedPaymentDetail)) {
            // set the owning side to null (unless already changed)
            if ($savedPaymentDetail->getUser() === $this) {
                $savedPaymentDetail->setUser(null);
            }
        }

        return $this;
    }

}
