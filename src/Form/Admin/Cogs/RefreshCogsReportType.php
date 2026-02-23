<?php

namespace App\Form\Admin\Cogs;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RefreshCogsReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('UPDATE_COGS_REPORT', CheckboxType::class, [
                'label' => 'Update COGS Report / Shipping Costs',
                'required' => true,
                'data' =>  true,
                'attr' => [
                    'class' => 'form-check-input',
                    'data-toggle' => 'tooltip',
                    'title' => 'Enable this option to update the cost of goods sold (COGS) report. This will update the cost of each order shipment, including the cost of the order itself.',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
