<?php

namespace App\Form\Admin\ProductType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class NewFrameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('frameType', Type\TextType::class, [
            'label' => 'Frame Type',
            'required' => true,
            'attr' => ['class' => 'form-control', 'placeholder' => 'Frame Type eg. single, premium'],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter frame type.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
