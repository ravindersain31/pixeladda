<?php

namespace App\Form\Admin\Warehouse;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Repository\OrderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class AddOrderIDAutocomplete extends AbstractType
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
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->in('O.status', [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]),
                    $qb->expr()->eq('O.isFreightRequired', true)
                ));
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
