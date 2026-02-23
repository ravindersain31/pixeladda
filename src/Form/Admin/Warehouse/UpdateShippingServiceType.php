<?php

namespace App\Form\Admin\Warehouse;

use App\Enum\Admin\WarehouseShippingServiceEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateShippingServiceType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $warehouseOrder = $options['warehouseOrder'];

        $builder->add('shippingService', Type\ChoiceType::class, [
            'label' => 'Shipping Service',
            'placeholder' => '-- Shipping Service --',
            'choices' => WarehouseShippingServiceEnum::makeFormChoices(),
            'required' => false,
            'data' => $warehouseOrder?->getShippingService() ?? null,
            'attr' => [
                'class' => 'form-select-sm'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
        $resolver->setRequired('warehouseOrder');
    }
}
