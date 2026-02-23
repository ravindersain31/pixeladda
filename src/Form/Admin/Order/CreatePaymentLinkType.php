<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CreatePaymentLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('amount', Type\TextType::class, [
            'label' => '<b>Link</b> Amount',
            'label_html' => true,
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Regex([
                    'pattern' => '/^\d+(\.\d{1,2})?$/',
                    'message' => 'Please enter a valid amount.',
                ]),
                new Constraints\GreaterThan(0),
            ]
        ]);
        $builder->add('internalNote', Type\TextareaType::class, [
            'label' => '<b>Internal</b> Note',
            'label_html' => true,
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);
        $builder->add('customerNote', Type\TextareaType::class, [
            'label' => '<b>Customer</b> Note',
            'label_html' => true,
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
