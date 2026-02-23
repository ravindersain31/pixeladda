<?php

namespace App\Form\Types;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSeoMetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', null, [
            'label' => 'Title Tag',
            'required' => false,
        ]);
        $builder->add('headerTag', null, [
            'label' => 'Header Tag',
            'required' => false,
        ]);

        $builder->add('description', Type\TextType::class, [
            'label' => 'Meta Description',
            'required' => false,
        ]);

        $builder->add('keywords', Type\TextType::class, [
            'label' => 'CSR - Search Keywords',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
