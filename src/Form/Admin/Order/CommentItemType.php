<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CommentItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $orderItem = $options['data'];

        $builder->add('itemDescription',Type\TextareaType::class,[
            'label' => 'Description',
            'attr' => [
                'placeholder' => 'Description',
                'maxlength' => 256,
                'minlength' => 3,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            OrderItem::class,
            'order' => null,
        ]);

        $resolver->setAllowedTypes('order', ['null', Order::class]);
    }
}
