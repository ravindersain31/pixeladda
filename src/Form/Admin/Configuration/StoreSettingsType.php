<?php

namespace App\Form\Admin\Configuration;

use App\Entity\StoreSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

class StoreSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('settingKey', Type\TextType::class, [
                'label' => 'Key',
            ])
            ->add('value', Type\TextType::class, [
                'label' => 'Value',
            ])
            ->add('isEnabled', Type\CheckboxType::class, [
                'label' => 'Is Enabled',
                'required' => false,
            ])
        ;
        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
        ]);
    }
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreSettings::class
        ]);
    }
}
