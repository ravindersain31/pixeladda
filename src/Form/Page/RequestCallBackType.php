<?php

namespace App\Form\Page;

use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class RequestCallBackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => 'Your Name',
            'attr' => [
                'placeholder' => 'Enter Your Full Name'
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your name.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
                new Constraints\Regex([
                    'pattern' => "/^(?=.*[A-Za-zÀ-ÿ])[A-Za-zÀ-ÿ' -]+$/u",
                    'message' => 'Please enter a valid name (letters only).',
                ]),
            ]
        ]);

        $builder->add('telephone', Type\TelType::class, [
            'label' => 'Phone Number',
            'attr' => ['placeholder' => '(XXX)-XXX-XXXX', 'data-numeric-input' => true,'inputmode' => 'numeric', 'data-telephone-input' => true],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter telephone.'),
                new Constraints\Length([
                    'min' => 10,
                    'minMessage' => 'The telephone must be at least {{ limit }} characters long.',
                ]),            
            ]
        ]);

        $builder->add('comment', Type\TextareaType::class, [
            'label' => 'Comment',
            'attr' => [
                'placeholder' => 'Enter your Message',
                'rows' => 8,
                'style' => 'height:70px !important'
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your comments.'),
                new Constraints\Length(['min' => 2]),
            ]
        ]);

        if (!empty($options['showRecaptcha'])) {
            $builder->add('recaptcha', RecaptchaType::class, [
                'mapped' => false,
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'showRecaptcha' => true,
        ]);
    }
}
