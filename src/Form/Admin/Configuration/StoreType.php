<?php

namespace App\Form\Admin\Configuration;

use App\Entity\Store;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Type\TextType::class, [
                'label' => 'Store Name',
            ])
            ->add('shortName', Type\TextType::class, [
                'label' => 'Short Name',
            ])
            ->add('storeDomains', LiveCollectionType::class, [
                'label' => 'Domain Alias',
                'entry_type' => StoreDomainType::class,
                'required' => true,
                'allow_add' => true,
                'button_add_options' => [
                    'label' => 'Add Domain Alias',
                    'attr' => [
                        'class' => 'btn btn-dark btn-sm',
                    ],
                ],
                'allow_delete' => true,
                'button_delete_options' => [
                    'label' => 'X',
                    'attr' => [
                        'class' => 'btn btn-danger',
                    ],
                ],
            ])
            ->add('isEnabled', Type\CheckboxType::class, [
                'label' => 'Is Enabled',
                'required' => false,
            ]);
        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
