<?php

namespace App\Form\Admin\Configuration;

use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AdminUserAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => 'Account Name',
        ]);

        $builder->add('username', Type\TextType::class, [
            'label' => 'Username',
            'constraints' => [
                new NotBlank(),
                new Regex('/^[a-zA-Z0-9_]+$/', 'Username should be consists of A-Z, a-z and 0-9 only.')
            ]
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => 'Email',
        ]);

        $builder->add('mobile', Type\TextType::class, [
            'label' => 'Mobile',
            'required' => false,
        ]);

        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'first_options' => [
                'label' => 'Password'
            ],
            'second_options' => [
                'label' => 'Confirm Password'
            ]
        ]);

        $builder->add('roles', EntityType::class, [
            'label' => 'Access Roles',
            'class' => Role::class,
            'placeholder' => '--- Select ---',
            'multiple' => true,
            'expanded' => true,
            'attr' => ['class' => 'd-flex role-list flex-wrap'],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Create Account',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
