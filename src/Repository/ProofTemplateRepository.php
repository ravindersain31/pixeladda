<?php

namespace App\Repository;

use App\Entity\ProofTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProofTemplate>
 */
class ProofTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProofTemplate::class);
    }

    public function fetchAll(): array
    {
        return $this->createQueryBuilder('pt')
            ->leftJoin('pt.proofFrameTemplates', 'pft')
            ->addSelect('pft')
            ->orderBy('pt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return ProofTemplate[] Returns an array of ProofTemplate objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProofTemplate
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
