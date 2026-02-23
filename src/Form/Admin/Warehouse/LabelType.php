<?php

namespace App\Form\Admin\Warehouse;

use App\Entity\Admin\WarehouseLabel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class LabelType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('color', Type\ColorType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
            'attr' => [
                'class' => 'p-0',
            ]
        ]);
        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WarehouseLabel::class,
//            'csrf_token_id' => 'category_form',
        ]);
    }
}
