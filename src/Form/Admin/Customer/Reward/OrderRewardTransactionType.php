<?php

namespace App\Form\Admin\Customer\Reward;

use App\Entity\Order;
use App\Entity\Reward\RewardTransaction;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class OrderRewardTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('points', NumberType::class, [
            'html5' => true,
            'required' => true,
            'label' => 'Reward Amount',
            'attr' => ['min' => 0.01,'placeholder' => 'Enter reward amount'],
            'constraints' => [
                new Constraints\Range([
                    'min' => 0.01,
                    'max' => 10000,
                    'invalidMessage' => 'Points must be between 0.01 and 10000',
                ]),
                new Constraints\NotBlank(message: 'Please enter a reward points'),
            ],
        ]);

        $builder->add('comment', TextareaType::class, [
            'required' => true,
            'label' => 'Comments',
            'attr' => ['rows' => 3, 'placeholder' => 'Enter comment'],
            'constraints' => [
                new Constraints\Length([
                    'min' => 3,
                ]),
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Submit',
            'attr' => ['class' => 'btn btn-primary'],
            'row_attr' => ['class' => 'w-100'],
            'label_html' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RewardTransaction::class,
        ]);
    }
}
