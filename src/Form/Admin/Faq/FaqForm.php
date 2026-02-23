<?php

namespace App\Form\Admin\Faq;

use App\Entity\Admin\Faq\Faq;
use App\Entity\Admin\Faq\FaqType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FaqForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', EntityType::class, [
                'class' => FaqType::class,
                'choice_label' => 'name',
                'placeholder' => 'Select FAQ Type',
                'label' => 'FAQ Type',
            ])
            ->add('question', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Question cannot be empty'),
                ],
            ])
            ->add('answer', TextareaType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Answer cannot be empty'),
                ],
                'attr' => [
                    'rows' => 6, 
                    'placeholder' => 'Enter the answer here...',
                ],
            ])
            ->add('showOnEditor', CheckboxType::class, [
                'required' => false,
                'label' => 'Show on Editor'
            ])
            ->add('keywords', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'keywords-hidden'],
                'autocomplete' => true,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
                'help' => 'Enter keywords, press Enter to add new tags.',
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Update' : 'Create',
                'attr' => ['class' => 'btn btn-primary mt-2']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Faq::class,
            'is_edit' => false
        ]);
    }
}
