<?php

namespace App\Form\Admin\Customer;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;


class UserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('password', Type\PasswordType::class, [
            'label' => false,
            'required' => true,
            'always_empty' => false,
            'attr' => [
                'placeholder' => "Enter Password"
            ],
            'constraints' => [
                new Constraints\Length(['min' => 6, 'max' => 255]),
                new Constraints\NotBlank(message: 'Please enter your password.')
            ]
        ]);


        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Update Password',
            'attr' => [
                'class' => 'btn btn-primary'
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => User::class,
        ]);
    }
}
