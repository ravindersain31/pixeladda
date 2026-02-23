<?php

namespace App\Form\Admin\CloudFront;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CloudFrontInvalidateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('invalidate_path', TextType::class, [
                'required' => true,
                'label' => 'Path to Invalidate <small class="text-muted">(e.g. /index.html or /*)</small>',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'value' => '/*',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
