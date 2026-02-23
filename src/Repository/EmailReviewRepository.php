<?php

namespace App\Repository;

use App\Entity\EmailReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailReview>
 *
 * @method EmailReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailReview[]    findAll()
 * @method EmailReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailReview::class);
    }

    public function save(EmailReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function list(): Query
    {
        return $this->createQueryBuilder('E')
            ->orderBy('E.createdAt', 'DESC')
            ->getQuery();
    }

//    /**
//     * @return EmailReview[] Returns an array of EmailReview objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EmailReview
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
