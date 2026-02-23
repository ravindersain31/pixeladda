<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ForgotPasswordType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username', Type\TextType::class, [
            'label' => 'Username',
            'attr' => ['placeholder' => 'Enter Username'],
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Forgot Password',
            'attr' => ['class' => 'btn btn-primary']
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
