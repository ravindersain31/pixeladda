<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class AdminOrderChangeStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('comments', TextareaType::class, [
            'label' => 'Comments before sending back to Waiting on Customer',
            'required' => true,
            'attr' => [
                'rows' => 5,
                'placeholder' => 'Add comments or details here...',
            ],
            'constraints' => [
                new NotBlank(['message' => 'Please provide comments.']), 
                new Length([
                    'max' => 500,  // Max length of 500 characters
                    'maxMessage' => 'The comment cannot exceed {{ limit }} characters.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here if needed
        ]);
    }
}
