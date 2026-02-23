<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Enum\WarehousePrinterEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrinterNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $printers = WarehousePrinterEnum::PRINTERS;
        $printerChoices = [];
        foreach ($printers as $key => $printer) {
            $printerChoices[$printer['label']] = $key;
        }

        $builder->add('printerName', Type\ChoiceType::class, [
            'label' => 'Printer Name',
            'required' => false,
            'choices' => [
                '-- Select Printer Name -- ' => '',
                ...$printerChoices,
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
