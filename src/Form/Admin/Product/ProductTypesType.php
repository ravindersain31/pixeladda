<?php

namespace App\Form\Admin\Product;

use App\Entity\ProductType;
use App\Entity\Store;
use App\Enum\EspPercentageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class ProductTypesType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => 'Product Type Name',
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        if ($options['data']->getId()) {
            $builder->add('slug', Type\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ]);
        }
        $builder->add('store', EntityType::class, [
            'class' => Store::class,
            'choice_label' => 'name',
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('isCustomizable', Type\ChoiceType::class, [
            'label' => 'Customizable',
            'help' => 'Can this product type be customized by the user on the editor?',
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
        ]);

        $builder->add('allowCustomSize', Type\ChoiceType::class, [
            'label' => 'Allow Custom Size',
            'help' => 'Can this product type be allowed to have custom sizes option available for products?',
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
        ]);

        $builder->add('beforeLoginEspType', Type\ChoiceType::class, [
            'required' => false,
            'label' => 'Before Login ESP Type',
            'placeholder' => '-- Please Select --',
            'choices' => array_flip(EspPercentageType::getBeforeLoginTypes()),
            'help' => 'The method to Increase/Decrease ESP percentage on product price.',
            'expanded' => false,
            'multiple' => false,
        ]);

        $builder->add('beforeLoginEspPercentage', Type\NumberType::class, [
            'required' => false,           
            'label' => 'Befor Login ESP Percentage Discount',
            'help' => 'It will be used for yardsignpromo website (The percentage must be between 0 and 100)',
            'attr' => [
                'placeholder' => 'ESP Percentage Discount'
            ],
            'constraints' => [
                new Constraints\Range([
                    'min' => 0,
                    'max' => 100,
                    'notInRangeMessage' => 'ESP Percentage Discount must be between {{ min }} and {{ max }}.',
                ]),
            ], 
        ]);

        $builder->add('afterLoginEspType', Type\ChoiceType::class, [
            'required' => false,
            'label' => 'After Login ESP Type',
            'placeholder' => '-- Please Select --',
            'choices' => array_flip(EspPercentageType::getAfterLoginTypes()),
            'help' => 'The method to Increase/Decrease ESP percentage on product price.',
            'expanded' => false,
            'multiple' => false,
        ]);

        $builder->add('afterLoginEspPercentage', Type\NumberType::class, [
            'required' => false,           
            'label' => 'After Login ESP Percentage Discount',
            'help' => 'It will be used for yardsignpromo website (The percentage must be between 0 and 100)',
            'attr' => [
                'placeholder' => 'ESP Percentage Discount'
            ],
            'constraints' => [
                new Constraints\Range([
                    'min' => 0,
                    'max' => 100,
                    'notInRangeMessage' => 'ESP Percentage Discount must be between {{ min }} and {{ max }}.',
                ]),
            ], 
        ]);

        $builder->add('defaultVariants', LiveCollectionType::class, [
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
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
            ],
        ]);
        $builder->add('customVariants', LiveCollectionType::class, [
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
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
            ],
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
            'data_class' => ProductType::class,
//            'csrf_token_id' => 'category_form',
        ]);
    }
}
