<?php

namespace App\Form\Admin\Order\SplitOrder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use App\Form\Admin\Order\SplitOrder\SubOrderFieldCollectionType;
use App\Entity\Order;

class SplitOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /**
         * @var Order $order
         */
        $order = $options['order'];

        if($order->getSubOrders()->count() > 0) {
            $data = [[]];
        }else{
            $data = [
                [], []
            ];
        }

        $builder->add('subOrders', LiveCollectionType::class, [
            'entry_type' => SubOrderFieldCollectionType::class,
            'required' => true,
            'allow_add' => true,
            'button_add_options' => [
                'label' => 'Add Order',
                'attr' => [
                    'class' => 'btn btn-link p-0 btn-sm',
                ],
            ],
            'allow_delete' => true,
            'row_attr' => ['class' => 'd-none'],
            'button_delete_options' => [
                'label' => 'x',
                'attr' => [
                    'class' => 'btn btn-danger btn-sm ms-2',
                ],
            ],
            'data' => $data,
            'empty_data' => $data,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
        $resolver->setRequired('order');
    }
}
