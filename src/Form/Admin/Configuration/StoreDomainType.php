<?php

namespace App\Form\Admin\Configuration;

use App\Entity\Currency;
use App\Entity\StoreDomain;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreDomainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Type\TextType::class, [
                'label' => 'Name',
            ])
            ->add('domain', Type\TextType::class, [
                'label' => 'Hostname',
                'constraints' => [
                    new Hostname(),
                ]
            ])
            ->add('currency', EntityType::class, [
                'label' => 'Currency',
                'placeholder' => '-- Choose a currency --',
                'class' => Currency::class,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreDomain::class,
        ]);
    }
}
