<?php

namespace App\Form\Admin\Order\New;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\AddressType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewOrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => 'Name/SKU',
            'help' => 'Enter the name or SKU of the product.',
            'row_attr' => ['class' => 'mb-0 col-12 col-md-3'],
            'data' => 'CUSTOM'
        ]);
        $builder->add('width', Type\IntegerType::class, [
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
            'required' => true,
        ]);
        $builder->add('height', Type\IntegerType::class, [
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
            'required' => true,
        ]);
        $builder->add('quantity', Type\IntegerType::class, [
            'row_attr' => ['class' => 'mb-0 col-4 col-md-3'],
        ]);
        $builder->add('price', Type\MoneyType::class, [
            'currency' => 'USD',
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
        ]);
        $builder->add('sides', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'Single Sided' => 'SINGLE',
                'Double Sided' => 'DOUBLE',
            ],
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
            'data' => 'SINGLE',
            'required' => true,
        ]);
        $builder->add('shapes', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'Square/Rectangle' => 'SQUARE',
                'Circle' => 'CIRCLE',
                'Oval' => 'OVAL',
                'Custom' => 'CUSTOM',
                'Custom with Border' => 'CUSTOM_WITH_BORDER',
            ],
            'data' => 'SQUARE',
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
        ]);
        $builder->add('imprintColor', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                '1 Color' => 'ONE',
                '2 Color' => 'TWO',
                '3 Color' => 'THREE',
                'Unlimited Color' => 'UNLIMITED',
            ],
            'data' => 'UNLIMITED',
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
        ]);
        $builder->add('grommets', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'None' => 'NONE',
                'Top Center' => 'TOP_CENTER',
                'Top Corners' => 'TOP_CORNERS',
                'Four Corners' => 'ALL_FOUR_CORNERS',
                'Six Corners' => 'SIX_CORNERS',
                'Custom Placement' => 'CUSTOM_PLACEMENT',
            ],
            'data' => 'NONE',
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
        ]);
        $builder->add('grommetColor', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'No Color/Silver' => 'SILVER',
                'Black' => 'BLACK',
                'Gold' => 'GOLD',
            ],
            'data' => 'SILVER',
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
        ]);
        $builder->add('frame', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'No Frame' => 'NONE',
                'Standard 10"W X 24"H' => 'WIRE_STAKE_10X24',
                'Premium 10"W X 24"H' => 'WIRE_STAKE_10X24_PREMIUM',
                'Single 30"H' => 'WIRE_STAKE_10X30_SINGLE',
            ],
            'data' => 'NONE',
            'row_attr' => ['class' => 'mb-0 col-4 col-md-2'],
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
//            'data_class' => OrderItem::class,
        ]);
    }
}
