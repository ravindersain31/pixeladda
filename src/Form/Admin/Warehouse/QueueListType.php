<?php

namespace App\Form\Admin\Warehouse;

use App\Entity\Admin\WarehouseShipByList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class QueueListType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('shipBy', Type\DateType::class, [
            'widget' => 'single_text',
            'row_attr' => [
                'class' => 'mb-0',
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ],
            'attr' => [
                'class' => 'form-control-sm',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WarehouseShipByList::class
        ]);
    }
}
