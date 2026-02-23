<?php

namespace App\Component\Admin\Reward;

use App\Entity\Reward\RewardTransaction;
use App\Form\Admin\Customer\Reward\RewardTransactionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "RewardTransactionForm",
    template: "admin/customer/users/reward/reward_transaction.html.twig"
)]
class RewardTransactionForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?RewardTransaction $rewardTransaction;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            RewardTransactionType::class,
            $this->rewardTransaction
        );
    }
}