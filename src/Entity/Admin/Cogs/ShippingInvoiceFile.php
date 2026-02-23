<?php

namespace App\Entity\Admin\Cogs;

use App\Entity\Admin\ShippingInvoice;
use App\Entity\User;
use App\Repository\Admin\Cogs\ShippingInvoiceFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShippingInvoiceFileRepository::class)]
#[Vich\Uploadable]
class ShippingInvoiceFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $file = null;

    #[Vich\UploadableField(
        mapping: 'user_files',
        fileNameProperty: "file.name",
        size: "file.size",
        mimeType: "file.mimeType",
        originalName: "file.originalName",
        dimensions: "file.dimensions"
    )]
    private ?File $fileObject = null;

    #[ORM\OneToMany(mappedBy: 'file', targetEntity: ShippingInvoice::class)]
    private Collection $shippingInvoices;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 100)]
    private ?string $originalName = null;

    #[ORM\ManyToOne]
    private ?User $uploadedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $generatedAt = null;

    #[Assert\Choice(choices: ['UPS', 'FedEx', 'DHL', 'USPS'], message: 'Choose a valid carrier.')]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $carrier = null;

    public function __construct()
    {
        $this->file = new EmbeddedFile();
        $this->shippingInvoices = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getFile(): ?EmbeddedFile
    {
        return $this->file;
    }

    public function setFile(EmbeddedFile $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function setFileObject(?File $fileObject = null): void
    {
        $this->fileObject = $fileObject;

        if (null !== $fileObject) {
            $this->uploadedAt = new \DateTimeImmutable();
        }
    }

    public function getFileObject(): ?File
    {
        return $this->fileObject;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    /**
     * @return Collection<int, ShippingInvoice>
     */
    public function getShippingInvoices(): Collection
    {
        return $this->shippingInvoices;
    }

    public function addShippingInvoice(ShippingInvoice $shippingInvoice): static
    {
        if (!$this->shippingInvoices->contains($shippingInvoice)) {
            $this->shippingInvoices->add($shippingInvoice);
            $shippingInvoice->setFile($this);
        }

        return $this;
    }

    public function removeShippingInvoice(ShippingInvoice $shippingInvoice): static
    {
        if ($this->shippingInvoices->removeElement($shippingInvoice)) {
            // set the owning side to null (unless already changed)
            if ($shippingInvoice->getFile() === $this) {
                $shippingInvoice->setFile(null);
            }
        }

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

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(?\DateTimeImmutable $generatedAt): static
    {
        $this->generatedAt = $generatedAt;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }
}
