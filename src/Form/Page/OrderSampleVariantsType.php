<?php

namespace App\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class OrderSampleVariantsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $item = $options['editData'] ? end($options['editData']['items']) ?? null : null;
        $productId = null;
        if ($item) {
            $productId = $item['productId'];
        }
        foreach ($options['variants'] as $variant) {
            $builder->add($variant['name'], Type\IntegerType::class, [
                'label' => $variant['name'],
                'block_prefix' => $variant['name'],
                'attr' => [
                    'placeholder' => 'Enter Qty',
                    'data-image' => $variant['image'],
                    'inputmode' => 'numeric',
                    'data-numeric-input' => true,
                    'class' => 'disable-scroll',
                    'data-disable-zero' => true,
                    'data-scroll' => true
                ],
                'required' => false,
                'data' => $productId === $variant['productId'] ? $item['quantity'] : null,
                'constraints' => [
                    new Constraints\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Please Enter valid Quantity.',
                    ]),
                    new Constraints\Range([
                        'min' => 1,
                        'max' => 3,
                        'notInRangeMessage' => 'Enter quantity between {{ min }} and {{ max }}.'
                    ])
                ]
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);

        $resolver->setRequired('variants');
        $resolver->setRequired('editData');
    }
}
