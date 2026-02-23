<?php

namespace App\Form\Admin\Product\Collection;

use App\Form\Admin\Product\Fields\ShippingFields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class ProductTypeShippingCollection extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['data'];
        $domains = $options['domains'];
        $elements = $builder->getPropertyPath()->getElements();
        $propertyPath = reset($elements);

        $day = $data[$propertyPath]['day'] ?? null;
        $shipping = $data[$propertyPath]['shipping'] ?? [];

        $choices = [];
        for ($i = 1; $i <= 15; $i++) {
            $choices['+' . $i . ' Day'] = $i;
        }

        $builder->add('day', Type\ChoiceType::class, [
            'label' => false,
            'required' => true,
            'data' => $day,
            'choices' => $choices,
            'placeholder' => '-- Select Day --',
            'attr' => ['class' => 'select-day']
        ]);

        $builder->add('shipping', LiveCollectionType::class, [
            'label' => false,
            'data' => $shipping,
            'entry_type' => ShippingFields::class,
            'required' => true,
            'allow_add' => true,
            'label_attr' => ['class' => 'py-1 text-dark'],
            'button_add_options' => [
                'label' => 'Add Tier',
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
            'entry_options' => [
                'domains' => $domains,
            ],
            'attr' => ['class' => 'row']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
//        $resolver->setRequired('shipping');
        $resolver->setRequired('domains');
        $resolver->setDefaults([]);
    }

}
