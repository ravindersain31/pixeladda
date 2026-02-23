<?php

namespace App\Form;

use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ExclusiveOfferMobileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Enter Your Email',
            'attr' => [
                'placeholder' => 'Type Your Email',
                'class' => 'email'
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your email to subscribe.'),
                new Constraints\Email(message: 'Please enter a valid email address.')
            ]
        ]);

        $builder->add('recaptcha', ReCaptchaType::class, [
            'attr' => [
                'class' => 'google-recaptcha'
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