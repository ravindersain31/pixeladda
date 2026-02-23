<?php

namespace App\Repository;

use App\Entity\Admin\Faq\FaqType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqType>
 */
class FaqTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqType::class);
    }

    public function getMaxSortOrder(): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('MAX(t.sortOrder) as maxSort')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$qb;
    }

    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return FaqType[] Returns an array of FaqType objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FaqType
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
