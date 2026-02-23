<?php

namespace App\Event;

use App\Entity\AdminUser;
use App\Entity\AppUser;
use App\Entity\Reward\Reward;
use Symfony\Contracts\EventDispatcher\Event;

class RewardRedeemEvent extends Event
{

    const NAME = 'reward.redeem';

    public function __construct(private readonly Reward $reward, private readonly AppUser|AdminUser $user)
    {
    }

    public function getReward(): Reward
    {
        return $this->reward;
    }

    public function getUser(): AppUser|AdminUser
    {
        return $this->user;
    }
}