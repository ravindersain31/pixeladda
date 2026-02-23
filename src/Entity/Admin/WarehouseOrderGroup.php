<?php

namespace App\Entity\Admin;

use App\Repository\Admin\WarehouseOrderGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: WarehouseOrderGroupRepository::class)]
class WarehouseOrderGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apiData'])]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'warehouseOrderGroup', targetEntity: WarehouseOrder::class, cascade: ['remove'])]
    private Collection $orderGroup;

    #[ORM\Column(length: 255)]
    #[Groups(['apiData'])]
    private ?string $cardColor = null;

    public function __construct()
    {
        $this->orderGroup = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, WarehouseOrder>
     */
    public function getOrderGroup(): Collection
    {
        return $this->orderGroup;
    }

    #[Groups(['apiData'])]
    public function getOrderGroupList(): array
    {
        $warehouseOrders = [];
        foreach ($this->orderGroup as $orderGroup) {
            $warehouseOrders[] = [
                'id' => $orderGroup->getId(),
            ];
        }

        return $warehouseOrders;
    }

    public function addOrderGroup(WarehouseOrder $orderGroup): static
    {
        if (!$this->orderGroup->contains($orderGroup)) {
            $this->orderGroup->add($orderGroup);
            $orderGroup->setWarehouseOrderGroup($this);
        }

        return $this;
    }

    public function removeOrderGroup(WarehouseOrder $orderGroup): static
    {
        if ($this->orderGroup->removeElement($orderGroup)) {
            // set the owning side to null (unless already changed)
            if ($orderGroup->getWarehouseOrderGroup() === $this) {
                $orderGroup->setWarehouseOrderGroup(null);
            }
        }

        return $this;
    }

    public function getCardColor(): ?string
    {
        return $this->cardColor;
    }

    public function setCardColor(string $cardColor): static
    {
        $this->cardColor = $cardColor;

        return $this;
    }
}
