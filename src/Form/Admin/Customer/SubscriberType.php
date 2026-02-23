<?php

namespace App\Form\Admin\Customer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

class SubscriberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', Type\EmailType::class, [
            'label' => 'Email',
            'required' => false,
            'disabled' => true,
            'attr' => [
                'placeholder' => 'Email Address',
            ],
            'constraints' => [
                new Constraints\Email(),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('mobileAlert', Type\CheckboxType::class,[
            'required' => false,
        ]);

        $builder->add('offers', Type\CheckboxType::class,[
            'required' => false,
        ]);

        $builder->add('marketing', Type\CheckboxType::class,[
            'required' => false,
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Update',
        ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
