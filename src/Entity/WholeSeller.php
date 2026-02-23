<?php

namespace App\Entity;

use App\Repository\WholeSellerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WholeSellerRepository::class)]
class WholeSeller
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mobile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipcode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aboutCompany = null;

    #[ORM\ManyToOne(inversedBy: 'wholeSellers')]
    private ?State $state = null;

    #[ORM\ManyToOne(inversedBy: 'wholeSellers')]
    private ?Country $country = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?UserFile $wholeSellerImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hearAboutUs = null;

    #[ORM\OneToOne(inversedBy: 'wholeSeller', cascade: ['persist', 'remove'])]
    private ?AppUser $appUser = null;

    #[ORM\ManyToOne(inversedBy: 'wholeSellers')]
    private ?StoreDomain $storeDomain = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getAboutCompany(): ?string
    {
        return $this->aboutCompany;
    }

    public function setAboutCompany(?string $aboutCompany): static
    {
        $this->aboutCompany = $aboutCompany;

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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getWholeSellerImageFile(): ?UserFile
    {
        return $this->wholeSellerImageFile;
    }

    public function setWholeSellerImageFile(?UserFile $wholeSellerImageFile): static
    {
        $this->wholeSellerImageFile = $wholeSellerImageFile;

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

    public function getClientType(): ?string
    {
        return $this->clientType;
    }

    public function setClientType(?string $clientType): static
    {
        $this->clientType = $clientType;

        return $this;
    }

    public function getHearAboutUs(): ?string
    {
        return $this->hearAboutUs;
    }

    public function setHearAboutUs(?string $hearAboutUs): static
    {
        $this->hearAboutUs = $hearAboutUs;

        return $this;
    }

    public function getAppUser(): ?AppUser
    {
        return $this->appUser;
    }

    public function setAppUser(?AppUser $appUser): static
    {
        $this->appUser = $appUser;

        return $this;
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
}
