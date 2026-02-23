<?php

namespace App\Form\Admin\Reports;

use App\Entity\Reports\MonthlyCogsReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class CogsMonthlyDataUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('payrollCost', Type\TextType::class, [
            'required' => false,
            'constraints' => [
                new Constraints\GreaterThan(value: -1, message: 'Payroll cost must be greater than or equal to 0'),
            ],
        ]);
        $builder->add('fixedCost', Type\TextType::class, [
            'required' => false,
            'constraints' => [
                new Constraints\GreaterThan(value: -1, message: 'Fixed cost must be greater than or equal to 0'),
            ],
        ]);
        $builder->add('notes', Type\TextareaType::class, [
            'required' => false,
        ]);

        $builder->add('lineItems', LiveCollectionType::class, [
            'label' => 'Line Items',
            'entry_type' => LineItemType::class,
            'required' => true,
            'allow_add' => true,
            'label_attr' => ['class' => 'py-1 text-dark'],
            'button_add_options' => [
                'label' => 'Add Item',
                'attr' => [
                    'class' => 'btn btn-dark btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger btn-sm',
                ],
            ],
            'attr' => ['class' => 'row']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MonthlyCogsReport::class,
        ]);
    }
}
