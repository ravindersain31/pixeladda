<?php

namespace App\Form\Admin\Product;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('search', TextType::class, [
            'label' => 'Search',
            'attr' => ['placeholder' => 'Enter your query.'],
            'required' => false,
        ]);

        $builder->add('category', EntityType::class, [
            'label' => 'Category',
            'class' => Category::class,
            'required' => false,
            'placeholder' => '-- Select --',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}
