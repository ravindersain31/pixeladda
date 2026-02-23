<?php

namespace App\Form\Admin\Configuration;

use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Type\TextType::class, [
                'label' => 'Currency Name',
            ])
            ->add('code', Type\TextType::class, [
                'label' => 'Currency Code',
            ])
            ->add('symbol', Type\TextType::class, [
                'label' => 'Currency Symbol',
            ])
            ->add('rate', Type\TextType::class, [
                'label' => 'Current Rate (based on USD)',
            ]);
        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Currency::class,
        ]);
    }
}
