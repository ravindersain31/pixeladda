<?php

namespace App\Repository\Admin;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseOrderGroup>
 *
 * @method WarehouseOrderGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarehouseOrderGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarehouseOrderGroup[]    findAll()
 * @method WarehouseOrderGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarehouseOrderGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseOrderGroup::class);
    }

    public function save(WarehouseOrderGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WarehouseOrderGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function filterOrderPrinterNameShipBy(string $printerName, \DateTime $shipBy): ?WarehouseOrderGroup
    {
        $qb = $this->createQueryBuilder('w');

        $qb->leftJoin(WarehouseOrder::class, 'o', 'WITH', 'o.warehouseOrderGroup = w')
            ->andWhere($qb->expr()->eq('o.printerName', ':printerName'))
            ->andWhere($qb->expr()->eq('o.shipBy', ':shipBy'))
            ->setParameter('printerName', $printerName)
            ->setParameter('shipBy', $shipBy);

        $qb->orderBy('w.id', 'DESC')->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }


    //    /**
//     * @return WarehouseOrderGroup[] Returns an array of WarehouseOrderGroup objects
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

//    public function findOneBySomeField($value): ?WarehouseOrderGroup
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
