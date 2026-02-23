<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CreateParcelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('length', Type\NumberType::class, [
            'label' => 'Length (in)',
            'constraints' => [
                new Constraints\NotBlank(null, 'Enter Length')
            ],
            'attr' => ['placeholder' => 'Length', 'class' => 'p-2'],
        ]);
        $builder->add('width', Type\NumberType::class, [
            'label' => 'Width (in)',
            'constraints' => [
                new Constraints\NotBlank(null, 'Enter Width')
            ],
            'attr' => ['placeholder' => 'Width', 'class' => 'p-2'],
        ]);
        $builder->add('height', Type\NumberType::class, [
            'label' => 'Height (in)',
            'constraints' => [
                new Constraints\NotBlank(null, 'Enter Height')
            ],
            'attr' => ['placeholder' => 'Height', 'class' => 'p-2'],
        ]);
        $builder->add('weight', Type\NumberType::class, [
            'label' => 'Weight',
            'constraints' => [
                new Constraints\NotBlank(null, 'Enter Weight')
            ],
            'attr' => ['placeholder' => 'Weight', 'class' => 'p-2'],
        ]);
        $builder->add('unit', Type\ChoiceType::class, [
            'label' => 'Unit',
            'choices' => [
                'Ounces' => 'oz',
                'Pounds' => 'lb',
            ],
            'data' => 'lb',
            'attr' => [
                'class' => 'rounded-0 bg-light',
                'style' => 'background: none;padding: 0 10px',
            ]
        ]);

        $builder->add('value', Type\NumberType::class, [
            'label' => 'Parcel Value ($)',
            'constraints' => [
                new Constraints\NotBlank(message: 'Enter Value')
            ],
            'attr' => ['placeholder' => 'Shipment Value', 'class' => 'p-2'],
            'row_attr' => ['class' => 'mb-0 mt-1'],
        ]);

        $builder->add('internalNotes', Type\TextType::class, [
            'label' => 'Internal Notes',
            'attr' => ['class' => 'p-2'],
            'row_attr' => ['class' => 'mb-0 mt-1'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
