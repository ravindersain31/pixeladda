<?php

namespace App\Form\Admin\Warehouse;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderGroup;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Repository\Admin\WarehouseOrderRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class orderGroupType extends AbstractType
{ 
    public function __construct(
        private WarehouseOrderRepository $warehouseOrderRepository,
    ){}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WarehouseOrder $warehouse */
        $warehouse = $options['warehouseOrder'];

        $builder->add('group', EntityType::class, [
            'required' => true,
            'autocomplete' => true,
            'multiple' => true,
            'class' => WarehouseOrder::class,
            'expanded' => false,
            'max_results' => 20,
            'preload' => false,
            'mapped' => false,
            'label' => 'Order Group',
            'data' => $warehouse->getWarehouseOrderGroup()?->getOrderGroup() ?? null,
            'choice_label' => function (WarehouseOrder $warehouseOrder) {
                $order = $warehouseOrder->getOrder();
                return $order ? $order->getOrderId() : '';
            },
            'query_builder' => function (EntityRepository $entityRepository) use ($warehouse) {
                $qb = $this->warehouseOrderRepository->createQueryBuilder('W');
                $qb->leftJoin('W.order', 'O');
                $qb->andWhere('W.shipBy = :shipBy')
                    ->setParameter('shipBy', $warehouse->getShipBy());
                $qb->andWhere('W.printerName = :printer')
                    ->setParameter('printer', $warehouse->getPrinterName());
                $qb->andWhere('O.orderId != :orderId')
                    ->setParameter('orderId', $warehouse->getOrder()->getOrderId());
                $qb->andWhere($qb->expr()->notIn('W.printStatus', ':notPrintStatus'));
                $qb->setParameter('notPrintStatus', [WarehouseOrderStatusEnum::DONE]);
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->in('O.status', [
                            OrderStatusEnum::SENT_FOR_PRODUCTION,
                            OrderStatusEnum::READY_FOR_SHIPMENT,
                            OrderStatusEnum::SHIPPED
                        ]),
                        $qb->expr()->eq('O.isFreightRequired', true)
                    )
                );
                $qb->orderBy('O.orderAt', 'DESC');

                return $qb;
            },
            'constraints' => [
                new NotBlank(['message' => 'Order ID is required']),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'warehouseOrder' => null
        ]);

        $resolver->setRequired('warehouseOrder');
    }
}
