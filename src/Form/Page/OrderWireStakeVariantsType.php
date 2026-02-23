<?php

namespace App\Form\Page;

use App\Constant\Editor\Addons;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use App\Entity\Product;

class OrderWireStakeVariantsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $item = $options['editData'] ? end($options['editData']['items']) ?? null : null;

        /** @var array|Product $product */
        $product = $options['product'];
        $productId = null;
        if ($item) {
            $productId = $item['productId'];
        }
        foreach ($options['variants'] as $variant) {
            $builder->add($variant['name'], Type\IntegerType::class, [
                'label' => $variant['label'] ?? $variant['name']." Wire Stake",
                'block_prefix' => $variant['name'],
                'disabled' => !$product['isSelling'],
                'row_attr' => [
                    'class' => 'pb-3'
                ],
                'attr' => [
                    'placeholder' => 'Enter Qty',
                    'data-image' => $variant['image'],
                    'isSelling' => $product['isSelling'],
                    'inputmode' => 'numeric',
                    'data-numeric-input' => true,
                    'frameType' => Addons::getFrameTypeLabel($variant['name']),
                    'quality' => Addons::getFrameQuantityType($variant['name']),
                    'class' => 'disable-scroll wire-stake-input m-auto'
                ],
                'required' => false,
                'data' => $productId === $variant['productId'] ? $item['quantity'] : null,
                'constraints' => [
                    new Constraints\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Please Enter valid Quantity.',
                ]),
                    new Constraints\Range([
                        'min' => 1,
                        'max' => 100000,
                        'notInRangeMessage' => 'Enter quantity between {{ min }} and {{ max }}.'
                    ])
                ]
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);

        $resolver->setRequired('variants');
        $resolver->setRequired('editData');
        $resolver->setRequired('product');
    }
}
