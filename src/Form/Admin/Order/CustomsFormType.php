<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CustomsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('customsSigner', Type\TextType::class, [
            'label' => 'Signature',
            'required' => true,
            'data' => 'Kyle Mak',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter the name of the signer'),
            ]
        ]);

        $builder->add('eelPfc', Type\TextType::class, [
            'label' => 'EEL/PFC Number',
            'required' => false,
            'attr' => ['placeholder' => 'NOEEI 30.37(a)'],
        ]);


        $builder->add('nonDeliveryAction', Type\ChoiceType::class, [
            'label' => 'Non Delivery Action',
            'required' => true,
            'choices' => [
                'Return' => 'return',
                'Abandon' => 'abandon',
            ],
            'data' => 'return',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a non delivery action'),
            ]
        ]);
        $builder->add('contentType', Type\ChoiceType::class, [
            'label' => 'Content Type',
            'required' => true,
            'choices' => [
                'Other' => 'other',
                'Documents' => 'documents',
                'Gift' => 'gift',
                'Merchandise' => 'merchandise',
                'Returned Goods' => 'returned_goods',
                'Sample' => 'sample',
                'Dangerous Goods' => 'dangerous_goods',
                'Humanitarian Donation' => 'humanitarian_donation',
            ],
            'data' => 'other',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a content type'),
            ]
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
