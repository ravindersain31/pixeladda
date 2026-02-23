<?php 

namespace App\Form\Admin;

use App\Enum\Admin\CacheEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CacheClearFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach (CacheEnum::all() as $enum) {
            $choices[$enum->label()] = $enum->value;
        }

        $builder
            ->add('cache_key', ChoiceType::class, [
                'label' => 'Select Cache Pool',
                'choices' => $choices,
                'required' => false,
                'placeholder' => '--- Select Pool ---',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
