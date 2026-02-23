<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class AdminRepeatOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderId', Type\TextType::class, [
            'label' => 'Order ID',
            'required' => false,
            'help' => 'Enter the order ID for this order, or leave blank to generate one automatically.',
            'row_attr' => ['class' => 'mb-0'],
            'constraints' => [
                new Constraints\Length(max: 40),
                new Constraints\Regex(pattern: '/^[a-zA-Z0-9-]+$/', message: 'Order ID can only contain letters, numbers, and hyphens.')
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
