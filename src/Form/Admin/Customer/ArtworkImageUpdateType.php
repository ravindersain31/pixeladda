<?php

namespace App\Form\Admin\Customer;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArtworkImageUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('old_image_name', TextType::class, [
        'label' => 'Current Image Name',
        'required' => true,
    ])
    ->add('new_image_name', TextType::class, [
        'label' => 'New Image Name',
        'required' => true,
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
