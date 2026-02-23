<?php

namespace App\Form\Admin\Category;

use App\Entity\Category;
use App\Entity\CategoryBlocks;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

class CategoryBlocksEmbeddedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', Type\TextType::class, [
            'label' => 'Title',
            'row_attr' => ['class' => 'col-12 col-md-6'],
        ]);

        $builder->add('position', Type\IntegerType::class, [
            'row_attr' => ['class' => 'col-12 col-md-2'],
        ]);

        $builder->add('linkToCategory', EntityType::class, [
            'class' => Category::class,
            'placeholder' => '-- Select --',
            'label' => 'Load More Link to Category',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('C')
                    ->orderBy('C.displayInMenu', 'DESC')
                    ->andWhere('C.isEnabled = :enabled')
                    ->setParameter('enabled', true);
            },
            'row_attr' => ['class' => 'col-12 col-md-4']
        ]);


        $builder->add('products', Type\TextType::class, [
            'label' => 'Product SKUs',
            'row_attr' => ['data-role' => 'tagsinput', 'class' => 'col-12 mt-4'],
        ]);

        $builder->add('isEnabled', Type\CheckboxType::class, [
            'label' => 'Is Enabled',
            'required' => false,
            'row_attr' => ['class' => 'col-12 mt-4']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategoryBlocks::class,
        ]);
    }
}
