<?php

namespace App\Form\Admin\Configuration;

use App\Entity\StoreSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

class DailyCapacityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', Type\NumberType::class, [
                'label' => 'Daily Capacity',
                'data' => $options['data']['value'],
                'attr' => ['placeholder' => 'Enter value', 'inputmode' => 'numeric', 'data-numeric-input' => true,],
                'constraints' => [
                    new Constraints\NotNull(message: 'Please enter some value'),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null
        ]);
    }
}
