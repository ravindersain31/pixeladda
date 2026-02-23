<?php

namespace App\Form\Admin\Warehouse;

use App\Enum\Admin\WarehouseShippingServiceEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateDriveLinkType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $warehouseOrder = $options['warehouseOrder'];

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
    }
}
