<?php

namespace App\Entity;

use App\Repository\ProofWireStakeTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProofWireStakeTemplateRepository::class)]
#[Vich\Uploadable]
class ProofWireStakeTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $wireStakeType = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $image = null;

    #[Vich\UploadableField(
        mapping: 'proof_template',
        fileNameProperty: "image.name",
        size: "image.size",
        mimeType: "image.mimeType",
        originalName: "image.originalName",
        dimensions: "image.dimensions"
    )]
    private ?File $imageFile = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->image = new EmbeddedFile();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWireStakeType(): ?string
    {
        return $this->wireStakeType;
    }

    public function setWireStakeType(string $wireStakeType): static
    {
        $this->wireStakeType = $wireStakeType;
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

    #[Groups(['apiData'])]
    public function getImageUrl(): ?string
    {
        return $this->image->getName() ? 'https://static.yardsignplus.com/storage/proof-template/' . $this->image->getName() : null;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}