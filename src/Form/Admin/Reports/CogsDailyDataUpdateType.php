<?php

namespace App\Form\Admin\Reports;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CogsDailyDataUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $materialBreakdown = $options['materialBreakdown'];

        $inkSheets = $materialBreakdown['inkCost'] ?? [
            "inkCost" => 0,
            "inkCostDoubleSided" => 0,
            "inkCostSingleSided" => 0,
            "sheetsDoubleSidedPrint" => 0,
            "sheetsSingleSidedPrint" => 0,
        ];

        $builder->add('sheetsSingleSidedPrint', Type\TextType::class, [
            'label' => 'Sheets - Single Sided Print',
            'required' => false,
            'constraints' => [
                new Constraints\GreaterThan(value: -1, message: 'Single Sided Sheets must be greater than or equal to 0'),
            ],
            'data' => $inkSheets['sheetsSingleSidedPrint'],
        ]);

        $builder->add('sheetsDoubleSidedPrint', Type\TextType::class, [
            'label' => 'Sheets - Double Sided Print',
            'required' => false,
            'constraints' => [
                new Constraints\GreaterThan(value: -1, message: 'Double Sided Sheets must be greater than or equal to 0'),
            ],
            'data' => $inkSheets['sheetsDoubleSidedPrint'],
        ]);

        $wireStakes = $materialBreakdown['wireStake'] ?? [
            "wireStakeCost" => 0,
            "wireStakeUsed" => 0,
            "singleWireStakeCost" => 0
        ];
        $builder->add('stakes', Type\TextType::class, [
            'label' => 'Wire Stakes',
            'required' => false,
            'constraints' => [
                new Constraints\GreaterThan(value: -1, message: 'Wire Stakes must be greater than or equal to 0'),
            ],
            'data' => $wireStakes['wireStakeUsed'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('materialBreakdown');
    }
}
