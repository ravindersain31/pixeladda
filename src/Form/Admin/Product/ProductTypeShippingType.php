<?php

namespace App\Form\Admin\Product;

use App\Form\Admin\Product\Collection\ProductTypeShippingCollection;
use App\Form\Admin\Product\Fields\ShippingFields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class ProductTypeShippingType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['productType'];
        $store = $data->getStore();
        $domains = $store->getStoreDomains();

        $shippingData = array_values(array_map(function ($item) {
            $item['shipping'] = array_values($item['shipping']);
            return $item;
        }, $data->getShipping()));

        $builder->add('days', LiveCollectionType::class, [
            'label' => false,
            'data' => $shippingData,
            'entry_type' => ProductTypeShippingCollection::class,
            'required' => true,
            'allow_add' => true,
            'label_attr' => ['class' => 'py-1 text-dark'],
            'button_add_options' => [
                'label' => 'Add Shipping Day',
                'attr' => [
                    'class' => 'btn btn-link p-0 btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'Remove',
                'attr' => [
                    'class' => 'btn btn-link text-danger btn-sm',
                ],
            ],
            'row_attr' => ['class' => 'd-none'],
            'entry_options' => [
                'label' => false,
                'row_attr' => ['class' => 'd-none'],
                'data' => $shippingData,
                'domains' => $domains,
            ],
            'attr' => ['class' => 'row']
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
        $resolver->setRequired('productType');
        $resolver->setDefaults([
        ]);
    }

}
