<?php

namespace App\Form\Admin\Warehouse;

use App\Entity\Admin\WarehouseOrderLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderLogType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', Type\TextareaType::class, [
            'label' => 'Comment',
            'row_attr' => ['class' => 'flex-fill'],
            'constraints' => [
                new NotBlank(message: 'Please enter a comment.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WarehouseOrderLog::class,
//            'csrf_token_id' => 'category_form',
        ]);
    }
}
