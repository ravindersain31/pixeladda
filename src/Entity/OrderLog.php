<?php

namespace App\Entity;

use App\Repository\OrderLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderLogRepository::class)]
class OrderLog
{

    const ORDER_CREATED = 'ORDER_CREATED';

    const ORDER_UPDATED = 'ORDER_UPDATED';

    const ORDER_STATUS_UPDATED = 'ORDER_STATUS_UPDATED';

    const PAYMENT_STATUS_UPDATED = 'PAYMENT_STATUS_UPDATED';

    const CHANGES_REQUESTED = 'CHANGES_REQUESTED';

    const PROOF_UPLOADED = 'PROOF_UPLOADED';

    const PROOF_DELETED = 'PROOF_DELETED';

    const SHIPPING_UPDATE = 'SHIPPING_UPDATE';

    public const TYPE_ORDER_DESIGNER_ASSIGNED = 'ORDER_DESIGNER_ASSIGNED';

    public const TYPE_ORDER_NOTE = 'ORDER_NOTE';

    public const TYPE_ORDER_ITEM_NOTE = 'ORDER_ITEM_NOTE';


    public const TYPE_INTERNAL = 'ORDER_TYPE_INTERNAL';


    public const ORDER_ADDRESS_UPDATED = 'ORDER_ADDRESS_UPDATED';

    public const DESIGNER_PROOF_STATUS = 'DESIGNER_PROOF_STATUS';

    public const DESIGNER_PRINT_FILE_STATUS = 'DESIGNER_PRINT_FILE_STATUS';

    public const DESIGNER_REVIEWER_PROOF_STATUS = 'DESIGNER_REVIEWER_PROOF_STATUS';

    public const DESIGNER_REVIEWER_PRINT_FILE_STATUS = 'DESIGNER_REVIEWER_PRINT_FILE_STATUS';

    public const PROOF_PRE_APPROVED = 'PROOF_PRE_APPROVED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'orderLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $changedBy = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->setType('NONE');
        $this->setData([]);
        $this->setCreatedAt(new \DateTimeImmutable());
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;

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

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

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
}
