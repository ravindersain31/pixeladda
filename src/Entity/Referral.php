<?php

namespace App\Entity;

use App\Entity\Admin\Coupon;
use App\Repository\ReferralRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferralRepository::class)]
class Referral
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'referrals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AppUser $referrer = null;

    #[ORM\ManyToOne(inversedBy: 'referrals')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AppUser $referred = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $referralCode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Coupon $coupon = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferrer(): ?AppUser
    {
        return $this->referrer;
    }

    public function setReferrer(?AppUser $referrer): static
    {
        $this->referrer = $referrer;

        return $this;
    }

    public function getReferred(): ?AppUser
    {
        return $this->referred;
    }

    public function setReferred(?AppUser $referred): static
    {
        $this->referred = $referred;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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
}
