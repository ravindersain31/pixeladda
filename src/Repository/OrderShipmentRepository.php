<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderShipment;
use App\Enum\OrderShipmentTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<OrderShipment>
 *
 * @method OrderShipment|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderShipment|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderShipment[]    findAll()
 * @method OrderShipment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderShipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderShipment::class);
    }

    public function save(OrderShipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderShipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function isOrderAllShipmentsDelivered(Order $order): bool
    {
        $qb = $this->createQueryBuilder('OS');

        $qb->select('COUNT(OS.id)');

        $qb->andWhere($qb->expr()->eq('OS.order', ':order'));
        $qb->andWhere($qb->expr()->neq('OS.status', ':status'));
        $qb->andWhere($qb->expr()->isNull('OS.refundedAt'));
        $qb->setParameter('order', $order);
        $qb->setParameter('status', 'delivered');

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count == 0;
    }

    public function numberOfBatchExists(Order $order, OrderShipmentTypeEnum $shipmentTypeEnum): int
    {
        $qb = $this->createQueryBuilder('OS');

        $qb->select('COUNT(OS.id)');

        $qb->andWhere($qb->expr()->eq('OS.order', ':order'));
        $qb->setParameter('order', $order);

        $qb->andWhere($qb->expr()->eq('OS.type', ':type'));
        $qb->setParameter('type', $shipmentTypeEnum->value);

        $qb->andWhere($qb->expr()->isNull('OS.refundedAt'));

        $qb->groupBy('OS.batchNum');

        $query = $qb->getQuery();

        return count($query->getResult());
    }

    public function getMaxBatchNumber(Order $order, OrderShipmentTypeEnum $shipmentTypeEnum): int
    {
        $qb = $this->createQueryBuilder('OS');

        $qb->select('MAX(OS.batchNum)')
            ->where('OS.order = :order')
            ->andWhere('OS.type = :type')
            ->andWhere('OS.refundedAt IS NULL')
            ->setParameter('order', $order)
            ->setParameter('type', $shipmentTypeEnum->value);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? (int) $result : 0;
    }


    public function shipmentInBatchOfOrder(Order $order, int $batch, OrderShipmentTypeEnum $shipmentTypeEnum)
    {
        $qb = $this->createQueryBuilder('OS');

        $qb->select('OS');

        $qb->andWhere($qb->expr()->eq('OS.batchNum', ':batch'));
        $qb->setParameter('batch', $batch);

        $qb->andWhere($qb->expr()->eq('OS.order', ':order'));
        $qb->setParameter('order', $order);

        $qb->andWhere($qb->expr()->eq('OS.type', ':type'));
        $qb->setParameter('type', $shipmentTypeEnum->value);

        $qb->andWhere($qb->expr()->isNull('OS.refundedAt'));

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findShipmentsGroupedByBatch(Order $order, ?OrderShipmentTypeEnum $type = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.order = :order')
            ->andWhere('s.refundedAt IS NULL')
            ->orderBy('s.batchNum', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->setParameter('order', $order);

        if ($type !== null) {
            $qb->andWhere('s.type = :type')
                ->setParameter('type', $type);
        }

        $results = $qb->getQuery()->getResult();

        // Group by batchNum in PHP
        $grouped = [];
        foreach ($results as $shipment) {
            $batch = $shipment->getBatchNum();
            if (!isset($grouped[$batch])) {
                $grouped[$batch] = [];
            }
            $grouped[$batch][] = $shipment;
        }

        ksort($grouped);

        return $grouped;
    }



    //    /**
//     * @return OrderShipment[] Returns an array of OrderShipment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OrderShipment
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
