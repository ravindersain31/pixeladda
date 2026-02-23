<?php

namespace App\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class UnsubscribeUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', HiddenType::class, [
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please provide a valid URL along with an email address.',
                ]),
                new Constraints\Email([
                    'message' => 'Please provide a valid URL along with an email address.',
                ]),
            ]
        ])
        ->add('unsubscribe', ChoiceType::class, [
            'label' => false,
            'choices' => [
                'Offers' => 'offers',
                'Marketing Emails' => 'marketing_emails',
            ],
            'multiple' => true,
            'expanded' => true,
            'attr' => ['class' => 'unsubscribe-options d-flex gap-5'],
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'At least one of the fields must be checked.',
                ]),
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}