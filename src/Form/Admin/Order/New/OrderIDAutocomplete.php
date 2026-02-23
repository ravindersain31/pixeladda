<?php

namespace App\Form\Admin\Order\New;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Repository\OrderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class OrderIDAutocomplete extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Order::class,
            'label' => false,
            'choice_label' => 'orderId',
            'max_results' => 20,
            'preload' => false,
            'query_builder' => function (OrderRepository $orderRepository) {
                $qb = $orderRepository->createQueryBuilder('O');
                $qb->orderBy('O.orderAt', 'DESC');
                return $qb;
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
