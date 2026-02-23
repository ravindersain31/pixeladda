<?php

namespace App\Form\Admin\Customer;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ArtworkFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Search',
            ])
            ->add('image_name', TextType::class, [
                'required' => false,
                'label' => 'Image Name',
            ]);
    }
}

