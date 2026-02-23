<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class EmailReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'attr' => [
                'placeholder' => 'Please enter your name',
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'This field is required.'),
            ]
        ]);

        $builder->add('rating', ChoiceType::class, [
            'label' => 'Rating',
            'choices' => [
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
            ],
            'expanded' => true,
            'row_attr' => ['class' => 'mb-3'],
            'attr' => ['class' => 'd-inline-flex',],
            'label_attr' => ['class' => 'me-3'],
            'constraints' => [
                new Constraints\NotBlank(message: 'This field is required.'),
            ]
        ]);

        $builder->add('comments', TextareaType::class, [
            'label' => 'Comments',
            'attr' => [
                'placeholder' => 'Please enter your comments',
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'This field is required.'),
            ]
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit',
            'attr' => [
                'class' => 'btn btn-primary btn-ysp-outline',
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
