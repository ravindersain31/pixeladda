<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ChangePasswordType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('currentPassword', Type\PasswordType::class, [
            'label' => 'Current Password',
            'required' => true,
            'attr' => ['placeholder' => 'Enter current password'],
            'always_empty' => false,
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);

        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Length(['min' => 6, 'max' => 255]),
            ],
            'invalid_message' => 'New and confirm password must be the same',
            'first_options' => [
                'label' => 'New Password',
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'password',
                    'placeholder' => "Enter new password"
                ],
            ],
            'second_options' => [
                'label' => 'Confirm Password',
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'password',
                    'placeholder' => "Confirm new password"
                ],
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Change Password',
            'attr' => ['class' => 'btn btn-primary']
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
