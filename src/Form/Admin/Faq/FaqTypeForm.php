<?php

namespace App\Form\Admin\Faq;

use App\Entity\Admin\Faq\FaqType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FaqTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Type Name'
            ])
            
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Sort Order',
                'required' => false,
                'help' => 'Lower numbers appear first. Default: 0',
            ])
            ->add('isEnabled', CheckboxType::class, [
                'label' => 'Enabled',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Update' : 'Create',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FaqType::class,
            'is_edit' => false, 
        ]);
    }
}
