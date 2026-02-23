<?php

namespace App\Repository\Admin;

use App\Entity\Admin\WarehouseShipByList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseShipByList>
 *
 * @method WarehouseShipByList|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarehouseShipByList|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarehouseShipByList[]    findAll()
 * @method WarehouseShipByList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarehouseShipByListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseShipByList::class);
    }

    public function save(WarehouseShipByList $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WarehouseShipByList $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveListByPrinter(string $printer): array
    {
        $qb = $this->createQueryBuilder('SL');
        $qb->andWhere($qb->expr()->eq('SL.printerName', ':printer'));
        $qb->setParameter('printer', $printer);
        $qb->andWhere($qb->expr()->isNull('SL.deletedAt'));
        $qb->orderBy('SL.shipBy', 'ASC');
        return $qb->getQuery()->getResult();
    }

    public function isShipByListActive(\DateTimeImmutable $shipBy, string $printer): bool
    {
        $qb = $this->createQueryBuilder('SL');
        $qb->select('COUNT(SL.id)');
        $qb->andWhere($qb->expr()->eq('SL.printerName', ':printer'));
        $qb->setParameter('printer', $printer);
        $qb->andWhere($qb->expr()->eq('SL.shipBy', ':shipBy'));
        $qb->setParameter('shipBy', $shipBy->format('Y-m-d'));
        $qb->andWhere($qb->expr()->isNull('SL.deletedAt'));
        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

//    /**
//     * @return WarehouseShipByList[] Returns an array of WarehouseShipByList objects
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

//    public function findOneBySomeField($value): ?WarehouseShipByList
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
