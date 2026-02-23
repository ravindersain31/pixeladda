<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CustomerPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank,
                new Constraints\Length(['min' => 6, 'max' => 255]),
            ],
            'invalid_message' => 'Please verify your new password matches when confirming.',
            'first_options' => [
                'label' => 'Password',
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'password',
                    'placeholder' => "Enter Confirm Password"
                ],
            ],
            'mapped' => false,
            'second_options' => [
                'label' => 'Confirm Password',
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'password',
                    'placeholder' => "Enter New Password"
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => User::class
        ]);
    }
}
