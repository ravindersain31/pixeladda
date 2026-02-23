<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ChargeItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('itemName',Type\TextType::class,[
            'label' => 'Item Name',
            'attr' => [
                'placeholder' => 'Item Name',
                'maxlength' => 25,
                'minlength' => 3,
            ],
            'help' => 'Enter the item name within 25 characters.',
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);
        $builder->add('totalAmount',Type\NumberType::class,[
            'label' => 'Charge Amount',
            'attr' => [
                'placeholder' => 'Charge Amount',
                'min' => 0,
                'step' => 0.01,
                'max' => 1000000,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Positive(),
                new Constraints\GreaterThan(0),
            ]
        ]);
        $builder->add('itemDescription',Type\TextareaType::class,[
            'label' => 'Description',
            'attr' => [
                'placeholder' => 'Description',
                'maxlength' => 256,
                'minlength' => 3,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'order' => null,
        ]);

        $resolver->setAllowedTypes('order', ['null', Order::class]);
    }
}
