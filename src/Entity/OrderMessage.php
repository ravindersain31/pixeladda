<?php

namespace App\Entity;

use App\Repository\OrderMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderMessageRepository::class)]
class OrderMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderMessages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sentBy = null;

    #[ORM\Column(length: 30)]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'orderMessage', targetEntity: UserFile::class, cascade: ['persist'])]
    private Collection $files;

    #[ORM\OneToMany(mappedBy: 'orderMessage', targetEntity: AdminFile::class, cascade: ['persist'])]
    private Collection $adminFiles;

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isBlank = null;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->adminFiles = new ArrayCollection();
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getSentBy(): ?User
    {
        return $this->sentBy;
    }

    public function setSentBy(?User $sentBy): static
    {
        $this->sentBy = $sentBy;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, UserFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(UserFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setOrderMessage($this);
        }

        return $this;
    }

    public function removeFile(UserFile $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getOrderMessage() === $this) {
                $file->setOrderMessage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AdminFile>
     */
    public function getAdminFiles(): Collection
    {
        return $this->adminFiles;
    }

    public function addAdminFile(AdminFile $adminFile): static
    {
        if (!$this->adminFiles->contains($adminFile)) {
            $this->adminFiles->add($adminFile);
            $adminFile->setOrderMessage($this);
        }

        return $this;
    }

    public function removeAdminFile(AdminFile $adminFile): static
    {
        if ($this->adminFiles->removeElement($adminFile)) {
            // set the owning side to null (unless already changed)
            if ($adminFile->getOrderMessage() === $this) {
                $adminFile->setOrderMessage(null);
            }
        }

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function isBlank(): ?bool
    {
        return $this->isBlank;
    }

    public function setIsBlank(?bool $isBlank): static
    {
        $this->isBlank = $isBlank;

        return $this;
    }
}
