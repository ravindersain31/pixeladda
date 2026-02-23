<?php

namespace App\Repository\Admin;

use App\Entity\Admin\WarehouseOrderLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseOrderLog>
 *
 * @method WarehouseOrderLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarehouseOrderLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarehouseOrderLog[]    findAll()
 * @method WarehouseOrderLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarehouseOrderLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseOrderLog::class);
    }

    public function save(WarehouseOrderLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WarehouseOrderLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return WarehouseOrderLog[] Returns an array of WarehouseOrderLog objects
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

//    public function findOneBySomeField($value): ?WarehouseOrderLog
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
