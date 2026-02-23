<?php

namespace App\Entity;

use App\Repository\UserFileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Vich\EmbeddedFile;

#[ORM\Entity(repositoryClass: UserFileRepository::class)]
#[Vich\Uploadable]
class UserFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $uploadedBy = null;

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

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?OrderMessage $orderMessage = null;

    #[ORM\Column(length: 3)]
    private ?string $version = null;

    public const FILE_TYPE = [
        'GENERAL' => 'GENERAL',
        'WHOLE_SELLER' => 'WHOLE_SELLER',
    ];

    public function __construct()
    {
        $this->file = new EmbeddedFile();
        $this->type = self::FILE_TYPE['GENERAL'];
        $this->version = 'V2';
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
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

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getOrderMessage(): ?OrderMessage
    {
        return $this->orderMessage;
    }

    public function setOrderMessage(?OrderMessage $orderMessage): static
    {
        $this->orderMessage = $orderMessage;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version ?? 'V2';
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

}
