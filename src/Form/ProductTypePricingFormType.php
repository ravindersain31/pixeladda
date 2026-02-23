<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductTypePricingFormType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setAction($this->urlGenerator->generate('fetch_pricing_product_type'));
        $builder->setMethod('POST');

        $builder
            ->add('productType', ChoiceType::class, [
                'choices' => [
                    'Yard Signs' => 'yard-sign',
                    'Hand Fans' => 'hand-fans',
                    'Big Head Cutouts' => 'big-head-cutouts',
                    'Die-Cut Signs' => 'die-cut',
                    'Blank Signs' => 'blank-signs',
                    'Yard Letters' => 'yard-letters',
                ],
                'data' => $options['default_slug'],
                'label' => "Select Product Type:",
                'label_attr' => ['class' => 'mb-0 fw-semibold text-nowrap'],
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'default_slug' => 'yard-sign',
            'attr' => [
                'id' => 'productTypePricingForm',
                'class' => 'product-type-form d-inline-block'
            ]
        ]);
    }
}
