<?php

namespace App\Entity;

use App\Repository\ProofTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProofTemplateRepository::class)]
#[Vich\Uploadable]
class ProofTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['apiData'])]
    private ?string $size = null;

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

    /**
     * @var Collection<int, ProofFrameTemplate>
     */
    #[ORM\OneToMany(targetEntity: ProofFrameTemplate::class, mappedBy: 'proofTemplate', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['apiData'])]
    private Collection $proofFrameTemplates;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->proofFrameTemplates = new ArrayCollection();
        $this->image = new EmbeddedFile();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size;

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

    #[Groups(['apiData'])]
    public function getImageUrl(): ?string
    {
        return $this->image->getName() ? 'https://static.yardsignplus.com/storage/proof-template/' . $this->image->getName() : null;
    }

    /**
     * @return Collection<int, ProofFrameTemplate>
     */
    public function getProofFrameTemplates(): Collection
    {
        return $this->proofFrameTemplates;
    }

    public function addProofFrameTemplate(ProofFrameTemplate $proofFrameTemplate): static
    {
        if (!$this->proofFrameTemplates->contains($proofFrameTemplate)) {
            $this->proofFrameTemplates->add($proofFrameTemplate);
            $proofFrameTemplate->setProofTemplate($this);
        }

        return $this;
    }

    public function removeProofFrameTemplate(ProofFrameTemplate $proofFrameTemplate): static
    {
        if ($this->proofFrameTemplates->removeElement($proofFrameTemplate)) {
            // set the owning side to null (unless already changed)
            if ($proofFrameTemplate->getProofTemplate() === $this) {
                $proofFrameTemplate->setProofTemplate(null);
            }
        }

        return $this;
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
