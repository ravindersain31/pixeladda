<?php

namespace App\Form\Admin\Customer\Reward;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RewardTransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fromEmail', EmailType::class, [
                'label' => 'From Email',
                'attr' => ['readonly' => true],
                'data' => $options['fromEmail'] ?? null,
            ])
            ->add('toEmail', EmailType::class, [
                'label' => 'To Email',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter recipient email']),
                    new Email(['message' => 'Please enter a valid email address']),
                ],
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Reward Points',
                'data' => $options['availableReward'] ?? null,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter reward points to transfer']),
                    new Positive(['message' => 'Reward points must be positive']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'fromEmail' => null,
            'availableReward' => null,
        ]);
    }
}
