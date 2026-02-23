<?php

namespace App\Entity;

use App\Repository\DistributorRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: DistributorRepository::class)]
class Distributor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]

    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 100)]
    
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $businessWebsite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $salesExperience = null;

    #[ORM\Column(length: 100)]
    private ?string $businessType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $expectedMonthlyOrderVolume = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $additionalComments = null;

    #[ORM\ManyToOne(inversedBy: 'distributors')]
    private ?StoreDomain $storeDomain = null;

    #[ORM\ManyToOne(inversedBy: 'distributors')]
    private ?Country $country = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $status = self::STATUS_OPEN;
    public const STATUS_OPEN = 0;
    public const STATUS_CLOSED = 1;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'distributors')]
    private ?State $state = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getBusinessWebsite(): ?string
    {
        return $this->businessWebsite;
    }

    public function setBusinessWebsite(?string $businessWebsite): self
    {
        $this->businessWebsite = $businessWebsite;
        return $this;
    }

    public function getSalesExperience(): ?string
    {
        return $this->salesExperience;
    }

    public function setSalesExperience(?string $salesExperience): self
    {
        $this->salesExperience = $salesExperience;
        return $this;
    }

    public function getBusinessType(): ?string
    {
        return $this->businessType;
    }

    public function setBusinessType(string $businessType): self
    {
        $this->businessType = $businessType;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): self
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getExpectedMonthlyOrderVolume(): ?string
    {
        return $this->expectedMonthlyOrderVolume;
    }

    public function setExpectedMonthlyOrderVolume(?string $expectedMonthlyOrderVolume): self
    {
        $this->expectedMonthlyOrderVolume = $expectedMonthlyOrderVolume;
        return $this;
    }

    public function getAdditionalComments(): ?string
    {
        return $this->additionalComments;
    }

    public function setAdditionalComments(?string $additionalComments): self
    {
        $this->additionalComments = $additionalComments;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

       public function getStatusLabel(): string
    {
        return match ($this->status) {
            0 => 'OPEN',
            1 => 'CLOSED',
            default => 'UNKNOWN',
        };
    }
    public function getStoreDomain(): ?StoreDomain
    {
        return $this->storeDomain;
    }

    public function setStoreDomain(?StoreDomain $storeDomain): static
    {
        $this->storeDomain = $storeDomain;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}
