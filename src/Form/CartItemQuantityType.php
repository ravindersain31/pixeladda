<?php

namespace App\Form;

use App\Entity\CartItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CartItemQuantityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemId', HiddenType::class)

            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 100000,
                    'class' => 'd-inline quantity-field border text-center p-0',
                    'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')", 
                ],
                'label' => false,
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank(message: 'Please enter quantity.'),
                    new Constraints\NotNull(message: 'Please enter some value'),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CartItem::class,
        ]);
    }
}
