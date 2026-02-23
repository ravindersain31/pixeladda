<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RepeatOrderFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('date', Type\DateType::class, [
            'label' => 'Select Date',
            'widget' => 'single_text',
            'attr' => [
                'class' => ''
            ],
            'constraints' => [
                new NotBlank()
            ]
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-primary mx-2 '
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
