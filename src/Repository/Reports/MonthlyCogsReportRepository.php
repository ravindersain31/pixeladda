<?php

namespace App\Repository\Reports;

use App\Entity\Reports\MonthlyCogsReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MonthlyCogsReport>
 *
 * @method MonthlyCogsReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method MonthlyCogsReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method MonthlyCogsReport[]    findAll()
 * @method MonthlyCogsReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonthlyCogsReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonthlyCogsReport::class);
    }

    public function save(MonthlyCogsReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MonthlyCogsReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MonthlyCogsReport[] Returns an array of MonthlyCogsReport objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MonthlyCogsReport
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
