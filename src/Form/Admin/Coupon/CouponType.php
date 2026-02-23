<?php

namespace App\Form\Admin\Coupon;

use App\Entity\Admin\Coupon;
use App\Entity\Store;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('couponName', Type\TextType::class, [
            'label' => 'Coupon Name',
            'required' => true,
            'attr' => [
                'placeholder' => 'Coupon Code'
            ],
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);
        $builder->add('code', Type\TextType::class, [
            'label' => 'Code',
            'required' => true,
            'attr' => [
                'placeholder' => 'Coupon Code'
            ],
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);
        $builder->add('discount', Type\NumberType::class, [
            'label' => 'Discount value (P/F)',
            'required' => true,
            'attr' => [
                'placeholder' => 'Coupon Code'
            ],
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);
        $builder->add('maximumDiscount', Type\NumberType::class, [
            'label' => 'Maximum Discount',
            'required' => false,
            'attr' => [
                'placeholder' => 'Maximum Discount'
            ],
        ]);
        $builder->add('minimumQuantity', Type\NumberType::class, [
            'label' => 'Minimum Quantity',
            'required' => false,
            'attr' => [
                'placeholder' => 'Minimum Quantity'
            ],
        ]);
        $builder->add('maximumQuantity', Type\NumberType::class, [
            'label' => 'Maximum Quantity',
            'required' => false,
            'attr' => [
                'placeholder' => 'Maximum Quantity'
            ],
        ]);
        $builder->add('type', Type\ChoiceType::class, [
            'label' => 'Type (Percentage/Flat)',
            'required' => true,
            'choices' => [
                'Percentage' => 'P',
                'Flat' => 'F',
            ],
            'preferred_choices' => ['P'],
        ]);
        $builder->add('minCartValue', Type\IntegerType::class, [
            'label' => 'Minimum Cart Value',
            'required' => false,
            'help' => 'Enter the minimum Cart Value',
            'attr' => [
                'placeholder' => '50',
            ]
        ]);
        $builder->add('usesTotal', Type\IntegerType::class, [
            'label' => 'Total Uses',
            'required' => true,
            'help' => 'Uses Total',
            'attr' => [
                'placeholder' => 'Uses Total',
            ],
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);
        
        $builder->add('isEnabled');

        $builder->add('isPromotional', Type\CheckboxType::class, [
            'required' => false,
            'label' => 'Is Promotional',
            'help' => 'Is Promotional: use to show on cart coupon disclaimer page',
            'attr' => [
                'placeholder' => 'Promotional',
            ]
        ]);

        $builder->add('store', EntityType::class, [
            'class' => Store::class,
            'required' => true,
            'placeholder' => '-- Select Store --',
            'constraints' => [
                new Constraints\NotNull(),
            ]
        ]);

        $builder->add('startDate', Type\DateTimeType::class, [
            'widget' => 'single_text',
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);
        $builder->add('endDate', Type\DateTimeType::class, [
            'widget' => 'single_text',
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank()
            ]
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'submit',
            'attr' => [
                'class' => 'btn btn-dark',
            ],

        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Coupon::class,
        ]);
    }
}
