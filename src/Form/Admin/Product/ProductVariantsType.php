<?php

namespace App\Form\Admin\Product;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class ProductVariantsType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /** @var Product $product */
        $product = $options['data'];
        $variants = $this->entityManager->getRepository(Product::class)->findActiveVariants($product);

        $builder->add('variants', LiveCollectionType::class, [
            'label' => false,
            'entry_type' => ProductVariantType::class,
            'entry_options' => [
                'variants' => $variants,
                'product' => $product,
            ],
            'data' => $variants,
            'required' => true,
            'allow_add' => true,
            'button_add_options' => [
                'label' => 'Add Variant',
                'attr' => [
                    'class' => 'btn btn-dark btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'Remove',
                'attr' => [
                    'class' => 'btn btn-link text-danger btn-sm',
                ],
            ],
        ]);

        $builder->add('isCustomSize', Type\CheckboxType::class, [
            'label' => 'Enable Product Custom Size',
            'help' => 'Tick/Untick if you want to enable Enable Product Custom Size Option.',
            'required' => false,
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
            'data_class' => Product::class,
        ]);
    }
}
