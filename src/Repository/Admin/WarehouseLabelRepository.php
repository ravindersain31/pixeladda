<?php

namespace App\Repository\Admin;

use App\Entity\Admin\WarehouseLabel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseLabel>
 *
 * @method WarehouseLabel|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarehouseLabel|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarehouseLabel[]    findAll()
 * @method WarehouseLabel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarehouseLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseLabel::class);
    }

    public function save(WarehouseLabel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WarehouseLabel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllActive(): Query
    {
        $qb = $this->createQueryBuilder('W');

        $qb->where($qb->expr()->isNull('W.deletedAt'));

        return $qb->getQuery();
    }

//    /**
//     * @return WarehouseLabel[] Returns an array of WarehouseLabel objects
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

//    public function findOneBySomeField($value): ?WarehouseLabel
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
