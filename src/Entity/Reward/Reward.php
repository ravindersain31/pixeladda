<?php

namespace App\Entity\Reward;

use App\Entity\AppUser;
use App\Repository\Reward\RewardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RewardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Reward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'reward', targetEntity: RewardTransaction::class)]
    private Collection $rewardTransactions;

    #[ORM\Column]
    private ?float $totalPoints = null;

    #[ORM\Column]
    private ?float $usedPoints = null;

    #[ORM\Column]
    private ?float $availablePoints = null;

    #[ORM\Column]
    private ?float $pendingPoints = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?AppUser $user = null;

    #[ORM\OneToOne(mappedBy: 'reward', cascade: ['persist', 'remove'])]
    private ?AppUser $appUser = null;

    public function __construct()
    {
        $this->rewardTransactions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->totalPoints = 0;
        $this->usedPoints = 0;
        $this->availablePoints = 0;
        $this->pendingPoints = 0;
        $this->recalculatePoints();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->recalculatePoints();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, RewardTransaction>
    */
    public function getRewardTransactions(): Collection
    {
        return $this->rewardTransactions;
    }

    public function addRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if (!$this->rewardTransactions->contains($rewardTransaction)) {
            $this->rewardTransactions->add($rewardTransaction);
            $rewardTransaction->setReward($this);
            $this->recalculatePoints();
        }

        return $this;
    }

    public function removeRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if ($this->rewardTransactions->removeElement($rewardTransaction)) {
            // set the owning side to null (unless already changed)
            if ($rewardTransaction->getReward() === $this) {
                $rewardTransaction->setReward(null);
                $this->recalculatePoints();
            }
        }

        return $this;
    }

    public function getTotalPoints(): ?float
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(float $totalPoints): static
    {
        $this->totalPoints = $totalPoints;

        return $this;
    }

    public function getUsedPoints(): ?float
    {
        return $this->usedPoints;
    }

    public function setUsedPoints(float $usedPoints): static
    {
        $this->usedPoints = $usedPoints;

        return $this;
    }

    public function getAvailablePoints(): ?float
    {
        return $this->availablePoints;
    }

    public function setAvailablePoints(float $availablePoints): static
    {
        $this->availablePoints = $availablePoints;

        return $this;
    }

    public function getPendingPoints(): ?float
    {
        return $this->pendingPoints;
    }

    public function setPendingPoints(float $pendingPoints): static
    {
        $this->pendingPoints = $pendingPoints;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function recalculatePoints(): void
    {
        $totalPoints = 0;
        $usedPoints = 0;
        $pendingPoints = 0;

        foreach ($this->rewardTransactions as $transaction) {

            if ($transaction->getStatus() === RewardTransaction::STATUS_EXPIRED) {
                continue;
            }

            if ($transaction->getStatus() === RewardTransaction::STATUS_COMPLETED) {
                if ($transaction->getType() === RewardTransaction::CREDIT) {
                    $totalPoints += $transaction->getPoints();
                } elseif ($transaction->getType() === RewardTransaction::DEBIT) {
                    $usedPoints += $transaction->getPoints();
                }
            } elseif ($transaction->getStatus() === RewardTransaction::STATUS_PENDING) {
                if ($transaction->getType() === RewardTransaction::DEBIT) {
                    $pendingPoints += $transaction->getPoints();
                }
                elseif ($transaction->getType() === RewardTransaction::CREDIT) {
                    $pendingPoints += $transaction->getPoints();
                }
            }
        }

        $this->totalPoints = $totalPoints;
        $this->usedPoints = $usedPoints;
        $this->pendingPoints = $pendingPoints;
        $this->availablePoints = $totalPoints - $usedPoints;
    }

    public function getUser(): ?AppUser
    {
        return $this->user;
    }

    public function setUser(?AppUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAppUser(): ?AppUser
    {
        return $this->appUser;
    }

    public function setAppUser(?AppUser $appUser): static
    {
        // unset the owning side of the relation if necessary
        if ($appUser === null && $this->appUser !== null) {
            $this->appUser->setReward(null);
        }

        // set the owning side of the relation if necessary
        if ($appUser !== null && $appUser->getReward() !== $this) {
            $appUser->setReward($this);
        }

        $this->appUser = $appUser;

        return $this;
    }

    public function getExpiryAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt->modify('+6 months');
    }
}
