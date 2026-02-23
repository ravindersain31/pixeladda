<?php

namespace App\Entity;

use App\Repository\CustomerPhotosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: CustomerPhotosRepository::class)]
#[Vich\Uploadable]
class CustomerPhotos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $isEnabled = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $photo = null;

    #[Vich\UploadableField(
        mapping: 'customer_photos',
        fileNameProperty: "photo.name",
        size: "photo.size",
        mimeType: "photo.mimeType",
        originalName: "photo.originalName",
        dimensions: "photo.dimensions"
    )]
    private ?File $photoFile = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private array $migratedData = [];

    #[Groups(['apiData'])]
    private ?string $photoUrl = null;

    #[ORM\ManyToOne(inversedBy: 'customerPhotos')]
    private ?StoreDomain $storeDomain = null;

    public function __construct()
    {
        $this->photo = new EmbeddedFile;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPhoto(): ?EmbeddedFile
    {
        return $this->photo;
    }

    public function setPhoto(EmbeddedFile $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function setPhotoFile(?File $photoFile = null): void
    {
        $this->photoFile = $photoFile;

        if (null !== $photoFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
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

    public function getMigratedData(): array
    {
        return $this->migratedData;
    }

    public function setMigratedData(?array $migratedData): static
    {
        $this->migratedData = $migratedData;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return 'https://static.yardsignplus.com/fit-in/500x500/storage/customer-photos/'.$this->getPhoto()->getName();
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
