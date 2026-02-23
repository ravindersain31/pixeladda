<?php

namespace App\Repository\Reports;

use App\Entity\Reports\DailyCogsReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyCogsReport>
 *
 * @method DailyCogsReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method DailyCogsReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method DailyCogsReport[]    findAll()
 * @method DailyCogsReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DailyCogsReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyCogsReport::class);
    }

    public function save(DailyCogsReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DailyCogsReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAdsCostByDate(
        \DateTimeImmutable $date,
        string $platform
    ): mixed
    {
        $qb = $this->createQueryBuilder('d');

        switch ($platform) {
            case 'google':
                $qb->select('SUM(d.googleAdsSpent) as cost');
                break;
            case 'facebook':
                $qb->select('SUM(d.facebookAdsSpent) as cost');
                break;
            case 'bing':
                $qb->select('SUM(d.bingAdsSpent) as cost');
                break;
            case 'all':
                $qb->select('SUM(d.googleAdsSpent + d.facebookAdsSpent + d.bingAdsSpent) as cost');
            default:
                $qb->select('SUM(d.googleAdsSpent + d.facebookAdsSpent + d.bingAdsSpent) as cost');
        }

        $formattedDate = $date->format('Y-m-d');

        $qb->where('d.date = :date')
           ->setParameter('date', $formattedDate);

        $result = $qb->getQuery()->getSingleScalarResult() ?? 0;
        return (float)$result;
    }

//    /**
//     * @return Cogs[] Returns an array of Cogs objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Cogs
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
