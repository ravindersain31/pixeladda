<?php

namespace App\Form\Admin\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserChangePasswordType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'first_options' => [
                'label' => 'Password'
            ],
            'second_options' => [
                'label' => 'Confirm Password'
            ]
        ]);


        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Change Password',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
