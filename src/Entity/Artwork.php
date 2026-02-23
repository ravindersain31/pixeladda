<?php

namespace App\Entity;

use App\Repository\ArtworkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;

#[ORM\Entity(repositoryClass: ArtworkRepository::class)]
#[Vich\Uploadable]
class Artwork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'artworks')]
    #[Groups(['editor'])]
    private ?ArtworkCategory $category = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['editor'])]
    private ?string $tags = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $image = null;

    #[Vich\UploadableField(
        mapping: 'artwork_image',
        fileNameProperty: "image.name",
        size: "image.size",
        mimeType: "image.mimeType",
        originalName: "image.originalName",
        dimensions: "image.dimensions"
    )]
    private ?File $imageFile = null;

    #[Groups(['editor'])]
    #[SerializedName('image')]
    private string $imageName = '';

    #[ORM\Column]
    private ?bool $status = null;

    public function __construct()
    {
        $this->image = new EmbeddedFile();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?ArtworkCategory
    {
        return $this->category;
    }

    public function setCategory(?ArtworkCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(string $tags): static
    {
        $this->tags = $tags;

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
            $this->status = 1;
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @return string
     */
    public function getImageName(): string
    {
        return $this->image->getName();
    }

    public function setImageName(string $imageName): self
    {
        $this->imageName = $imageName;

        if ($this->image) {
            $this->image->setName($imageName);
        } else {
            $this->image = new EmbeddedFile();
            $this->image->setName($imageName);
        }

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }
}
