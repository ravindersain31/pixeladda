<?php

namespace App\Form\Admin\Order\New;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $addons = $options['data'] ?? [];
        $builder->add('sides', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'Single Sided' => 'SINGLE',
                'Double Sided' => 'DOUBLE',
            ],
            'mapped' => false,
            'data' => $addons['sides']['key'] ?? 'SINGLE',
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
            'mapped' => false,
            'data' => $addons['shapes']['key'] ?? 'SQUARE',
        ]);
        $builder->add('imprintColor', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                '1 Color' => 'ONE',
                '2 Color' => 'TWO',
                '3 Color' => 'THREE',
                'Unlimited Color' => 'UNLIMITED',
            ],
            'mapped' => false,
            'data' => $addons['imprintColor']['key'] ?? 'UNLIMITED',
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
            'mapped' => false,
            'data' => $addons['grommets']['key'] ?? 'NONE',
        ]);
        $builder->add('grommetColor', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'No Color/Silver' => 'SILVER',
                'Black' => 'BLACK',
                'Gold' => 'GOLD',
            ],
            'mapped' => false,
            'data' => $addons['grommetColor']['key'] ?? 'SILVER',
        ]);
        $builder->add('frame', Type\ChoiceType::class, [
            'placeholder' => '-- Select --',
            'choices' => [
                'No Frame' => 'NONE',
                'Standard 10"W X 24"H' => 'WIRE_STAKE_10X24',
                'Premium 10"W X 24"H' => 'WIRE_STAKE_10X24_PREMIUM',
                'Single 30"H' => 'WIRE_STAKE_10X30_SINGLE',
            ],
            'mapped' => false,
            'data' => $addons['frame']['key'] ?? 'NONE',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data' => [],
        ]);
    }
}
