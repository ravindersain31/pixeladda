<?php

namespace App\Form\Admin\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StripeInvoiceFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemName', Type\TextType::class, [
                'label' => 'Item Name',
                'constraints' => [
                    new Constraints\NotBlank(message: 'Item name required'),
                ]
            ])
            ->add('itemQuantity', Type\IntegerType::class, [
                'label' => 'Item Quantity',
                'constraints' => [
                    new Constraints\NotBlank(message: 'Item quantity required'),
                    new Constraints\GreaterThan([
                        'value' => 0,
                        'message' => 'Item quantity must be greater than 0',
                    ]),
                ]
            ])
            ->add('itemPrice', Type\NumberType::class, [
                'label' => 'Item Price',
                'constraints' => [
                    new Constraints\NotBlank(message: 'Item price required'),
                    new Constraints\GreaterThan([
                        'value' => 0.50,
                        'message' => 'Item price must be greater than 0.50',
                    ]),
                ],
                'scale' => 2, 
            ])
            ->add('itemDescription', Type\TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Constraints\NotBlank(message: 'Item description required'),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
