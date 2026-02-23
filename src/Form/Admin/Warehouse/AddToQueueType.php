<?php

namespace App\Form\Admin\Warehouse;

use App\Enum\WarehousePrinterEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class AddToQueueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $warehouseOrder = $options['warehouseOrder'];
        $printers = WarehousePrinterEnum::PRINTERS;
        $printerChoices = [];
        foreach ($printers as $key => $printer) {
            $printerChoices[$printer['label']] = $key;
        }

        $builder->add('printerName', Type\ChoiceType::class, [
            'label' => 'Printer',
            'choices' => [
                '-- Select Printer -- ' => '',
                ...$printerChoices,
            ],
            'data' => $warehouseOrder?->getPrinterName() ?? null,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a printer name.'),
            ]
        ]);

        $builder->add('shipBy', Type\DateType::class, [
            'widget' => 'single_text',
            'label' => 'Ship By',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a ship by date.'),
            ]
        ]);

        $builder->add('driveLink', Type\UrlType::class, [
            'label' => 'Drive Link',
            'default_protocol' => 'https',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter a drive link.'),
            ]
        ]);

        $builder->add('comments', Type\TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('warehouseOrder');
    }
}
