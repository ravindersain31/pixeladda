<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ChargeCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('amount', Type\NumberType::class, [
            'label' => '<b>Charge</b> Amount',
            'label_html' => true,
            'attr' => ['placeholder' => 'Enter amount', 'inputmode' => 'numeric', 'data-numeric-decimal-input' => true],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Regex([
                    'pattern' => '/^\d+(\.\d{1,2})?$/',
                    'message' => 'Please enter a valid amount.',
                ]),
                new Constraints\GreaterThan(0),
            ]
        ]);


        $builder->add('paymentNonce', Type\HiddenType::class);

        $builder->add('internalNote', Type\TextareaType::class, [
            'label' => '<b>Internal</b> Note',
            'label_html' => true,
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);

        $builder->add('customerNote', Type\TextareaType::class, [
            'label' => '<b>Customer</b> Note',
            'label_html' => true,
            'required' => false,
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
