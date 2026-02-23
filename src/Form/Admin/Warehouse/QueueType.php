<?php

namespace App\Form\Admin\Warehouse;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class QueueType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('lists', LiveCollectionType::class, [
            'entry_type' => QueueListType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn badge btn-danger btn-sm',
                ],
            ],
            'prototype' => true,
            'label' => false,
            'attr' => [
                'class' => 'd-none',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
        $resolver->setRequired([
            'printer',
        ]);
    }
}
