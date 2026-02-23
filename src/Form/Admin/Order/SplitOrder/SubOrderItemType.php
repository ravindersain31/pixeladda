<?php

namespace App\Form\Admin\Order\SplitOrder;

use App\Entity\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class SubOrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => 'Name/SKU',
            'help' => 'Enter the name or SKU of the product.',
            'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Name/SKU'],
            'data' => 'CUSTOM',
            'empty_data' => 'CUSTOM',
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter name or SKU.'),
            ],
        ]);

        $builder->add('width', Type\IntegerType::class, [
            'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Width'],
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter width.'),
                new Constraints\LessThanOrEqual(value: 1000, message: 'Width cannot be more than 1000.'),
                new Constraints\GreaterThanOrEqual(value: 1, message: 'Width cannot be less than 1.')
            ],
        ]);

        $builder->add('height', Type\TextType::class, [
            'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Height'],
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter Height.'),
                new Constraints\LessThanOrEqual(value: 1000, message: 'Height cannot be more than 1000.'),
                new Constraints\GreaterThanOrEqual(value: 1, message: 'Height cannot be less than 1.')
            ],
        ]);

        // // size should be in nxn in format
        // $builder->add('size', Type\IntegerType::class, [
        //     'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Size'],
        //     'required' => true,
        //     'constraints' => [
        //         new Constraints\NotBlank(message: 'Please enter Size.'),
        //         new Constraints\Regex(pattern: '/^[0-9]+x[0-9]+$/u', message: 'Size should be in the format n x n'),
        //     ],
        // ]);

        $builder->add('quantity', Type\IntegerType::class, [
            'required' => true,
            'label' => 'Quantity',
            'attr' => ['class' => 'form-control form-control-sm'],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter quantity.'),
                new Constraints\GreaterThanOrEqual(value: 1, message: 'Quantity cannot be less than 1.')
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => OrderItem::class,
        ]);
    }
}
