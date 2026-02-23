<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResendEmailsType extends AbstractType
{
    public const EMAILS_TYPES = [
        'ORDER_RECEIVED' => 'Order Received',
        'PROOF_APPROVED' => 'Proof Approved',
        'PROOF_UPLOADED' => 'Proof Uploaded',
        'CHANGE_REQUESTED' => 'Change Requested',
        'ORDER_SHIPPED' => 'Order Shipped',
        'ORDER_CANCELLED' => 'Order Cancelled',
        'ORDER_OUT_FOR_DELIVERY' => 'Order Out for Delivery',
        'ORDER_DELIVERED' => 'Order Delivered',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('emailTypes', Type\ChoiceType::class, [
            'label' => 'Select Email Types',
            'choices' => array_flip(self::EMAILS_TYPES),
            'required' => true,
            'multiple' => true,
            'autocomplete' => true,
            'placeholder' => '-- Select Email Type --',
            'attr' => [
                'class' => 'form-select-sm',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            Order::class,
            'order' => null,
        ]);

        $resolver->setAllowedTypes('order', ['null', Order::class]);
    }
}
