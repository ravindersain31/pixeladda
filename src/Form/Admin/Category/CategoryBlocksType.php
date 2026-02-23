<?php

namespace App\Form\Admin\Category;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class CategoryBlocksType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('categoryBlocks', LiveCollectionType::class, [
            'label' => false,
            'entry_type' => CategoryBlocksEmbeddedType::class,
            'required' => true,
            'allow_add' => true,
            'button_add_options' => [
                'label' => 'Add Block',
                'attr' => [
                        'class' => 'btn btn-dark btn-sm',
                    ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                        'class' => 'btn btn-danger btn-sm',
                    ],
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
