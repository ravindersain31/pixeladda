<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Length;

class RepeatOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderId', TextType::class, [
            'label' => 'Enter Order Number',
            'attr' => [
                'placeholder' => 'Enter Order Number',
                'class' => 'order_no',
                'data-numeric-input' => true,
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Order ID is required.'),
                new Constraints\Regex([
                    'pattern' => '/^[0-9]+$/',
                    'message' => 'Please enter a valid order ID.',
                ]),
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
