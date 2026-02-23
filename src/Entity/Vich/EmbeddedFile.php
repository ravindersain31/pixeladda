<?php

namespace App\Entity\Vich;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class EmbeddedFile
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    /**
     * @var array<int>|null
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $dimensions = null;

    public function __construct(
        ?string $name = null,
        ?string $originalName = null,
        ?string $mimeType = null,
        ?int $size = null,
        ?array $dimensions = null
    ){
        $this->name = $name;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->dimensions = $dimensions;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): self
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * Shortcut to get the image width.
     */
    public function getWidth(): ?int
    {
        return $this->dimensions ? ($this->dimensions[0] ?? null) : null;
    }

    /**
     * Shortcut to get the image height.
     */
    public function getHeight(): ?int
    {
        return $this->dimensions ? ($this->dimensions[1] ?? null) : null;
    }

    /**
     * Format image dimensions for HTML attributes to prevent layout shifting.
     */
    public function getHtmlDimensions(): ?string
    {
        if (null !== $this->dimensions) {
            $width = filter_var($this->getWidth(), FILTER_VALIDATE_INT);
            $height = filter_var($this->getHeight(), FILTER_VALIDATE_INT);
            if ($width !== false && $height !== false) {
                return sprintf('width="%d" height="%d"', $width, $height);
            }
        }
        return null;
    }
}
