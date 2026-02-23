<?php

namespace App\Form\Page;

use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

class ViewProofType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderId', Type\TextType::class, [
            'label' => 'Enter Order Number',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Order Number',
            ],
            'constraints' => [
                new Constraints\Length(['min' => 2]),
            ]
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => 'Enter Your Email',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter your Email'
            ],
            'constraints' => [
                new Constraints\Email(message: 'Please enter a valid email address.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('telephone', Type\TelType::class, [
            'label' => 'Enter Your Phone',
            'required' => false,
            'attr' => ['placeholder' => '(XXX)-XXX-XXXX', 'data-numeric-input' => true,'inputmode' => 'numeric', 'data-telephone-input' => true],
            'constraints' => [
                new Constraints\Length(['min' => 2]),
            ]
        ]);

        if (!empty($options['showRecaptcha'])) {
            $builder->add('recaptcha', ReCaptchaType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'showRecaptcha' => false,
        ]);
    }
}
