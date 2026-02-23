<?php

namespace App\Entity\Admin\Faq;

use App\Entity\Admin\Faq\FaqType;
use App\Repository\FaqRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqRepository::class)]
class Faq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'faqs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FaqType $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answer = null;

    #[ORM\Column(nullable: true)]
    private ?bool $showOnEditor = null;

    #[ORM\Column(nullable: true)]
    private ?array $keywords = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?FaqType
    {
        return $this->type;
    }

    public function setType(?FaqType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(?string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    public function isShowOnEditor(): ?bool
    {
        return $this->showOnEditor;
    }

    public function setShowOnEditor(?bool $showOnEditor): static
    {
        $this->showOnEditor = $showOnEditor;

        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords ?? [];
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }
}
