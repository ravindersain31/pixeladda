<?php

namespace App\Form\Admin\Product;

use App\Form\Admin\Product\Fields\PricingFields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class ProductTypePricingType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['productType'];
        $store = $data->getStore();
        $domains = $store->getStoreDomains();
        $variants = $data->getDefaultVariants();

        foreach ($variants as $variant) {
            $pricing = $data->getPricing();
            $builder->add('pricing_' . $variant, LiveCollectionType::class, [
                'label' => false,
                'data' => $pricing['pricing_' . $variant] ?? [],
//                'label' => 'Pricing for Variant: ' . $variant,
                'entry_type' => PricingFields::class,
                'required' => true,
                'allow_add' => true,
                'label_attr' => ['class' => 'py-1 text-dark'],
                'button_add_options' => [
                    'label' => 'Add Price',
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
                'row_attr' => ['class' => 'd-none'],
                'entry_options' => [
                    'domains' => $domains,
                ],
                'attr' => ['class' => 'row']
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('productType');
        $resolver->setDefaults([
        ]);
    }

}
