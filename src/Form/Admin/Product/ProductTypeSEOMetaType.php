<?php

namespace App\Form\Admin\Product;

use App\Constant\CustomSize;
use App\Form\Types\SeoMetaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductTypeSEOMetaType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['productType'];
        $category = $options['category'];
        $variants = $data->getDefaultVariants();
        $metaData = $data->getSeoMetaData();

        if($category->getSlug() == 'custom-signs') {
            $variants = array_merge($variants, CustomSize::SIZES);
        }

        foreach ($variants as $variant) {
            $builder->add('meta_data_' . $variant, SeoMetaType::class, [
                'label' => 'Meta Data for ' . $variant,
                'label_attr' => [
                    'class' => 'p-0',
                ],
                'data' => $metaData[$category->getSlug()]['meta_data_' . $variant] ?? [],
                'required' => false,
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save ' . $category->getName() . ' Variants Meta Data',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('productType');
        $resolver->setRequired('category');
        $resolver->setDefaults([
        ]);
    }

}
