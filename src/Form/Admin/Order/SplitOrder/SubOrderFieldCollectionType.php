<?php

namespace App\Form\Admin\Order\SplitOrder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use App\Form\Admin\Order\SplitOrder\SubOrderItemType;

class SubOrderFieldCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = [
            [
                'quantity' => 1,
            ],
        ];

        $builder->add('subOrderItems', LiveCollectionType::class, [
            'entry_type' => SubOrderItemType::class,
            'required' => true,
            'allow_add' => true,
            'button_add_options' => [
                'label' => 'Add Item',
                'attr' => [
                    'class' => 'btn btn-link p-0 btn-sm',
                ],
            ],
            'empty_data' => $data,
            'data' => $data,
            'allow_delete' => true,
            'row_attr' => ['class' => 'd-none'],
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger btn-sm ms-2',
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
