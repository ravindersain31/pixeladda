<?php

namespace App\Form\Admin\Warehouse;

use App\Enum\Admin\WarehouseShippingServiceEnum;
use App\Enum\OrderTagsEnum;
use App\Enum\WarehousePrinterEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class OrderUpdateType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $warehouseOrder = $options['warehouseOrder'];
        $order = $options['order'];

        $existingTags = $order?->getMetaDataKey('tags') ?? [];
        $deliveryMethod = $order?->getMetaDataKey('deliveryMethod') ?? [];
        $deliveryMethodKey = $deliveryMethod['key'] ?? '';
        $isFreeFreight = $order?->getMetaDataKey('isFreeFreight') ?? false;
        $isBlindShipping = $order?->getMetaDataKey('isBlindShipping') ?? false;
        $isSaturdayDelivery = $order?->getMetaDataKey('isSaturdayDelivery') ?? false;
        $mustShip = $order?->getMetaDataKey('mustShip') ?? null;
        $selectedTags = array_keys(array_filter($existingTags, fn($tag) => $tag['active'] ?? false));

        if ($deliveryMethodKey === 'REQUEST_PICKUP' || in_array('REQUEST_PICKUP', $selectedTags, true)) {
            if (!in_array('REQUEST_PICKUP', $selectedTags, true)) {
                $selectedTags[] = 'REQUEST_PICKUP';
            }
        }

        if ($isFreeFreight && !in_array('FREIGHT', $selectedTags, true)) {
            $selectedTags[] = 'FREIGHT';
        }

        if ($isBlindShipping && !in_array('BLIND_SHIPPING', $selectedTags, true)) {
            $selectedTags[] = 'BLIND_SHIPPING';
        }

        if ($isSaturdayDelivery && !in_array('SATURDAY_DELIVERY', $selectedTags, true)) {
            $selectedTags[] = 'SATURDAY_DELIVERY';
        }

        $printers = WarehousePrinterEnum::PRINTERS;
        $printerChoices = [];
        foreach ($printers as $key => $printer) {
            $printerChoices[$printer['label']] = $key;
        }

        $builder->add('printerName', Type\ChoiceType::class, [
            'label' => 'Printer',
            'placeholder' => '-- Printer --',
            'choices' => [
                ...$printerChoices,
            ],
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a printer.'),
            ],
            'data' => $warehouseOrder?->getPrinterName() ?? $order?->getPrinterName(),
            'attr' => [
                'class' => 'form-select-sm'
            ]
        ]);

        $builder->add('shipBy', Type\DateType::class, [
            'widget' => 'single_text',
            'label' => 'Ship By',
            'data' => $warehouseOrder?->getShipBy() ?? null,
            'attr' => [
                'class' => 'form-control-sm',
                'disabled' => $mustShip !== null
            ],
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a ship by date.'),
            ],
        ]);

        $builder->add('mustShip', Type\DateType::class, [
            'widget' => 'single_text',
            'label' => 'Must Ship',
            'data' => $mustShip && !empty($mustShip['date']) ? new \DateTime($mustShip['date']) : null,
            'attr' => [
                'class' => 'form-control-sm',
                'min' => (new \DateTime())->format('Y-m-d'),
            ],
            'required' => false,
        ]);

        $builder->add('shippingService', Type\ChoiceType::class, [
            'label' => 'Shipping Service',
            'placeholder' => '-- Shipping Service --',
            'choices' => WarehouseShippingServiceEnum::makeFormChoices(),
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a shipping service.'),
            ],
            'data' => WarehouseShippingServiceEnum::FEDEX_HOME,
            'attr' => [
                'class' => 'form-select-sm'
            ]
        ]);

        $builder->add('orderTag', Type\ChoiceType::class, [
            'label' => 'Order Tag',
            'choices' => array_flip(OrderTagsEnum::LABELS),
            'required' => false,
            'multiple' => true,
            'autocomplete' => true,
            'data' => $selectedTags,
            'attr' => [
                'class' => 'form-select-sm'
            ]
        ]);

        $builder->add('driveLink', Type\UrlType::class, [
            'label' => 'Drive Link',
            'default_protocol' => 'https',
            'data' => $warehouseOrder?->getDriveLink() ?? '',
            'attr' => [
                'class' => 'form-control-sm'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
        $resolver->setRequired('warehouseOrder');
        $resolver->setRequired('order');
    }
}
