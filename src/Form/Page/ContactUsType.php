<?php

namespace App\Form\Page;

use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ContactUsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => 'Full Name',
            'attr' => [
                'placeholder' => 'Full Name'
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your name.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => 'Email Address',
            'attr' => [
                'placeholder' => 'Email'
            ],
            'constraints' => [
                new Constraints\Email(message: 'Please enter a valid email address.'),
                new Constraints\NotBlank(message: 'Please enter your email.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('telephone', Type\TelType::class, [
            'label' => 'Phone Number',
            'attr' => ['placeholder' => 'Phone Number', 'inputmode' => 'numeric', 'data-phone-input' => true],
            'constraints' => [
                new Constraints\NotBlank(message: 'Phone number cannot be empty.'),
                new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
            ]
        ]);

        $builder->add('comment', Type\TextareaType::class, [
            'label' => 'Comment',
            'attr' => [
                'placeholder' => 'Enter Message',
                'rows' => 8,
                'style' => 'height:70px !important'
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your comments.'),
                new Constraints\Length(['min' => 2]),
            ]
        ]);

       
            $builder->add('recaptcha', ReCaptchaType::class, [
                'mapped' => true,
            ]);
      

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'showRecaptcha' => false,
        ]);
    }
}
