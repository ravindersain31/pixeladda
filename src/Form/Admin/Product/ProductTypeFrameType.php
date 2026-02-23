<?php

namespace App\Form\Admin\Product;

use App\Form\Admin\Product\Fields\PricingFields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use App\Entity\ProductType as ProductTypeEntity;

class ProductTypeFrameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductTypeEntity $data */
        $data = $options['productType'];
        $store = $data->getStore();
        $domains = $store->getStoreDomains();

        $pricingTypes = $data->getFramePricing();

        foreach ($pricingTypes as $type => $pricing) {
            usort($pricing, fn($a, $b) => $a['qty'] <=> $b['qty']);
            $builder->add($type, LiveCollectionType::class, [
                'label' => ucfirst(str_replace('pricing_', '', $type)) . ' Frame Pricing',
                'data' => $pricing,
                'entry_type' => PricingFields::class,
                'required' => true,
                'allow_add' => true,
                'label_attr' => ['class' => 'py-1 text-dark'],
                'button_add_options' => [
                    'label' => 'Add ' . ucfirst(str_replace('pricing_', '', $type)) . ' Price',
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
