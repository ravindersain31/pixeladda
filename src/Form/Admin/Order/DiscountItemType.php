<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class DiscountItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['order'];
        /** @var OrderItem $orderItem */
        $orderItem = $options['data'];

        if($orderItem instanceof OrderItem) {
            $maxDiscount = $order->getSubTotalAmount() + $orderItem->getTotalAmount();
        }else{
            $maxDiscount = $order->getSubTotalAmount();
        }


        $builder->add('itemName',Type\TextType::class,[
            'label' => 'Item Name',
            'attr' => [
                'placeholder' => 'Item Name',
                'maxlength' => 256,
                'minlength' => 3,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);
        $builder->add('totalAmount',Type\NumberType::class,[
            'label' => 'Discount Amount',
            'attr' => [
                'placeholder' => 'Discount Amount',
                'min' => 0,
                'step' => 0.01,
                'max' => $maxDiscount,
            ],
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Positive(),
                new Constraints\GreaterThan(0),
                new Constraints\LessThanOrEqual($maxDiscount, message: 'Discount amount cannot be greater than order subtotal total $'.$maxDiscount),
            ]
        ]);
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
