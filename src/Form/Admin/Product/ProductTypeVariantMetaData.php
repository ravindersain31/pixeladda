<?php 

namespace App\Form\Admin\Product;

use App\Entity\ProductType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductTypeVariantMetaData extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ProductType $productType */
        $productType = $options['productType'];
        $variants = $productType->getDefaultVariants() ?? []; 
        $variantMeta = $productType->getVariantMetaData() ?? []; 

        foreach ($variants as $index => $variant) {
            $data = $variantMeta[$variant] ?? ['label' => '', 'sort' => $index + 1];

            $builder->add("variant_label_$variant", TextType::class, [
                'mapped' => false, 
                'required' => false,
                'label' => "Label for $variant",
                'data' => $data['label'],
                'attr' => [
                    'placeholder' => "Label for $variant",
                    'class' => 'form-control',
                ],
            ]);

            $builder->add("variant_sort_$variant", IntegerType::class, [
                'mapped' => false,
                'required' => false,
                'label' => "Sort for $variant",
                'data' => $data['sort'],
                'attr' => [
                    'placeholder' => "Sort position for $variant",
                    'class' => 'form-control',
                    'min' => 1,
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductType::class,
        ]);
        $resolver->setRequired('productType');
    }
}
