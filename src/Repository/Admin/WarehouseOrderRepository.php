<?php

namespace App\Repository\Admin;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseShipByList;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\Admin\WarehouseShippingServiceEnum;
use App\Enum\OrderStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseOrder>
 *
 * @method WarehouseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarehouseOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarehouseOrder[]    findAll()
 * @method WarehouseOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarehouseOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseOrder::class);
    }

    public function save(WarehouseOrder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WarehouseOrder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countBy(
        array   $printers,
        bool    $hasPrintStatus = true,
        array   $printStatus = [],
        bool    $onlyUnassigned = false,
        array   $orderStatus = [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED],
        ?string $search = null,
        ?\DateTimeInterface $exceptShipByDate = null
    ): Query
    {
        $qb = $this->createQueryBuilder('WO');

        // Build a dynamic select clause to count each printer individually
        $selects = [];
        foreach (array_unique($printers) as $printer) {
            if ($printer === 'UNASSIGNED') {
                $selects[] = "SUM(CASE WHEN WO.printerName IS NULL OR WO.shipBy IS NULL OR WO.printerName = '' THEN 1 ELSE 0 END) as UNASSIGNED";
            } else {
                $selects[] = sprintf("SUM(CASE WHEN WO.printerName = '%s' THEN 1 ELSE 0 END) as %s", $printer, $printer);
            }
        }

        $qb->select(implode(', ', $selects));

        if (!$onlyUnassigned) {
            $qb->andWhere($qb->expr()->isNotNull('WO.printerName'));
            $qb->andWhere($qb->expr()->isNotNull('WO.shipBy'));
        }

        if ($onlyUnassigned) {
            $doneStatus = WarehouseOrderStatusEnum::DONE;
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->neq('WO.printStatus', ':doneStatus'),
                    $qb->expr()->isNull('WO.shipBy'),
                    $qb->expr()->isNull('WO.printerName')
                )
            );
            $qb->setParameter('doneStatus', $doneStatus);
            
        }

        if ($exceptShipByDate !== null) {
            $qb->andWhere('WO.shipBy < :shipByDate');
            $qb->setParameter('shipByDate', $exceptShipByDate->format('Y-m-d'));
        }

        if (count($printStatus) > 0) {
            if ($hasPrintStatus) {
                $qb->andWhere($qb->expr()->in('WO.printStatus', ':printStatus'));
            } else {
                $qb->andWhere($qb->expr()->notIn('WO.printStatus', ':printStatus'));
            }
            $qb->setParameter('printStatus', $printStatus);
        }

        $qb->leftJoin('WO.order', 'O');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->in('O.status', ':orderStatus'),
            $qb->expr()->eq('O.isFreightRequired', true)
        ));
        $qb->setParameter('orderStatus', $orderStatus);

        if ($search) {
            $this->addSearch($qb, $search);
        }

        return $qb->getQuery();
    }


    public function findOrderInDeletedShipBy(): Query
    {
        $qb = $this->createQueryBuilder('WO');

//        $qb->select('WO.printerName, WO.shipBy, WO.printStatus, O.orderId, O.status');

        $qb->leftJoin(WarehouseShipByList::class, 'SB', 'WITH', 'SB.shipBy = WO.shipBy AND SB.printerName = WO.printerName');

        $qb->andWhere($qb->expr()->isNotNull('SB.deletedAt'));

        $qb->andWhere($qb->expr()->isNotNull('WO.printerName'));
        $qb->andWhere($qb->expr()->isNotNull('WO.shipBy'));

        $qb->andWhere($qb->expr()->notIn('WO.printStatus', ':notPrintStatus'));
        $qb->setParameter('notPrintStatus', [WarehouseOrderStatusEnum::DONE]);

        $qb->leftJoin('WO.order', 'O');

        $qb->andWhere($qb->expr()->in('O.status', [OrderStatusEnum::SHIPPED, OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT]));

        $qb->orderBy('WO.shipBy', 'ASC');

        return $qb->getQuery();
    }

    public function findUnassigned(bool $onlyCount = false): Query
    {
        $qb = $this->createQueryBuilder('WO');
        if ($onlyCount) {
            $qb->select('COUNT(WO.id) as totalOrders');
        } else {
            $qb->select('WO');
        }

        $qb->leftJoin('WO.order', 'O');

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('WO.printerName'),
            $qb->expr()->isNull('WO.shipBy'),
        ));

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->in('O.status', [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]),
            $qb->expr()->eq('O.isFreightRequired', true)
        ));

        return $qb->getQuery();
    }

    public function searchOrder(?string $search): Query
    {
        $qb = $this->createQueryBuilder('WO');
        $qb->select('WO.printerName');
        $qb->leftJoin('WO.order', 'O');

        $qb->andWhere($qb->expr()->isNotNull('WO.shipBy'));

        $this->addSearch($qb, $search);

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->in('O.status', [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]),
            $qb->expr()->eq('O.isFreightRequired', true)
        ));

        $qb->andWhere($qb->expr()->notIn('WO.printStatus', ':notPrintStatus'));
        $qb->setParameter('notPrintStatus', [WarehouseOrderStatusEnum::DONE]);

        return $qb->getQuery();
    }

    public function findQueue(
        string     $printerName,
        bool       $onlyCount = false,
        array|null|\DateTime $shipBy = null,
        ?string    $search = null,
        ?\DateTimeInterface $exceptShipByDate = null
    ): Query
    {
        $qb = $this->createQueryBuilder('WO');
        if ($onlyCount) {
            $qb->select('COUNT(WO.id) as totalOrders');
        } else {
            $qb->select('WO');
        }

        $qb->leftJoin('WO.order', 'O');

        $qb->andWhere($qb->expr()->eq('WO.printerName', ':printerName'));
        $qb->setParameter('printerName', $printerName);

        $qb->andWhere($qb->expr()->isNotNull('WO.shipBy'));

        if ($search) {
            $this->addSearch($qb, $search);
        }

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->in('O.status', [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]),
            $qb->expr()->eq('O.isFreightRequired', true)
        ));

        $qb->andWhere($qb->expr()->notIn('WO.printStatus', ':notPrintStatus'));
        $qb->setParameter('notPrintStatus', [WarehouseOrderStatusEnum::DONE]);

        if (is_array($shipBy) && count($shipBy) > 0) {
            $qb->andWhere($qb->expr()->in('WO.shipBy', ':shipBy'));
            $qb->setParameter('shipBy', $shipBy);
        }elseif ($shipBy) {
            $qb->andWhere($qb->expr()->eq('WO.shipBy', ':shipBy'));
            $qb->setParameter('shipBy', $shipBy);
        }

        if ($exceptShipByDate !== null) {
            $qb->andWhere('WO.shipBy < :shipByDate');
            $qb->setParameter('shipByDate', $exceptShipByDate->format('Y-m-d'));
        }

        // Constructing the CASE expression manually
        $caseSql = 'CASE';
        foreach (WarehouseShippingServiceEnum::SHIPPING_SERVICE_ORDER as $service => $order) {
            $caseSql .= " WHEN WO.shippingService = :$service THEN $order";
            $qb->setParameter($service, $service);
        }
        $caseSql .= ' ELSE 9999 END';

        // Using the constructed CASE expression in ORDER BY
        $qb->orderBy($caseSql);

        return $qb->getQuery();
    }

    public function findDoneOrders(): Query
    {
        $qb = $this->createQueryBuilder('WO');
        $qb->leftJoin('WO.order', 'O');

        $qb->andWhere($qb->expr()->in('O.status', ':orderStatus'));
        $qb->setParameter('orderStatus', [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]);

        $qb->andWhere($qb->expr()->in('WO.printStatus', ':printStatus'));
        $qb->setParameter('printStatus', [WarehouseOrderStatusEnum::DONE]);

        $qb->orderBy('WO.id', 'DESC');

        return $qb->getQuery();
    }

    public function getOrdersWithPreTransitStatus(?string $shipByFrom = null, ?string $shipByTo = null): array
    {
        $qb = $this->createQueryBuilder('WO');

        $qb->leftJoin('WO.order', 'O');
        $qb->leftJoin('O.orderShipments', 'OS');

        $qb->andWhere($qb->expr()->in('O.status', ':orderStatus'));
        $qb->setParameter('orderStatus', [
            OrderStatusEnum::SENT_FOR_PRODUCTION,
            OrderStatusEnum::READY_FOR_SHIPMENT,
            OrderStatusEnum::SHIPPED
        ]);

        $qb->andWhere($qb->expr()->in('WO.printStatus', ':printStatus'));
        $qb->setParameter('printStatus', [WarehouseOrderStatusEnum::DONE]);

        $qb->andWhere($qb->expr()->in('OS.status', ':shipmentStatus'));
        $qb->setParameter('shipmentStatus', ['pre_transit']);

        if ($shipByFrom) {
            $qb->andWhere('WO.shipBy >= :shipByFrom');
            $qb->setParameter('shipByFrom', new \DateTime($shipByFrom));
        }

        if ($shipByTo) {
            $qb->andWhere('WO.shipBy <= :shipByTo');
            $qb->setParameter('shipByTo', new \DateTime($shipByTo));
        }

        $qb->orderBy('WO.id', 'DESC');

        return $qb->getQuery()->getResult();
    }



    private function addSearch($qb, string $search)
    {
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('O.orderId', ':search'),
            $qb->expr()->like($this->getReplaceStatement('O.billingAddress', 'phone'), ':search'),
            $qb->expr()->like($this->getReplaceStatement('O.shippingAddress', 'phone'), ':search'),
            $qb->expr()->like("JSON_EXTRACT(O.shippingAddress, '$.phone')", ':search'),
            $qb->expr()->like("JSON_EXTRACT(O.billingAddress, '$.phone')", ':search')
        ));
        $qb->setParameter('search', "%$search%");
    }

    private function getReplaceStatement(string $array, string $key): string
    {
        return sprintf("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(%s, '$.%s')), '(', ''), ')', ''), '-', ''), '+', ''), '.', ''), ' ', ''), '', '')", $array, $key);
    }

//    /**
//     * @return WarehouseOrder[] Returns an array of WarehouseOrder objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WarehouseOrder
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
