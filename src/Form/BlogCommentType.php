<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class BlogCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Enter Your Name',
            'attr' => [
                'placeholder' => 'Enter Your Name',
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your Name.'),
            ]
        ]);

        $builder->add('email', EmailType::class, [
            'label' => 'Enter Your Email',
            'attr' => [
                'placeholder' => 'Enter Your Email',
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your email.'),
                new Constraints\Email(message: 'Please enter a valid email address.')
            ]
        ]);

        $builder->add('comment', TextareaType::class, [
            'label' => 'Enter Your Comment',
            'attr' => [
                'placeholder' => 'Enter Your Comment',
            ],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please enter your comment.'),
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
