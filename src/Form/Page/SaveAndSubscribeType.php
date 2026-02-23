<?php

namespace App\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class SaveAndSubscribeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', Type\TextType::class, [
            'label' => 'Email',
            'attr' => [
                'placeholder' => 'Enter Your Email',
                'class' => 'email'
            ],
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please enter an email address',
                ]),
                new Constraints\Email([
                    'message' => 'Please enter a valid email address',
                ]),
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
