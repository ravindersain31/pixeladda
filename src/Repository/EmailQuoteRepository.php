<?php

namespace App\Repository;

use App\Entity\EmailQuote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailQuote>
 *
 * @method EmailQuote|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailQuote|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailQuote[]    findAll()
 * @method EmailQuote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailQuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailQuote::class);
    }

    public function save(EmailQuote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailQuote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function deleteExpired(int $expireAfterDays = 30): Query
    {
        $qb = $this->createQueryBuilder('EQ');
        $qb->delete();
        $qb->where($qb->expr()->lt('EQ.createdAt', ':expireAfter'));
        $qb->setParameter('expireAfter', (new \DateTime())->modify("-$expireAfterDays days"));

        return $qb->getQuery();
    }


     public function findPromoEmailQuoteCustomer($user): array
    {
        return $this->createQueryBuilder('sd')
            ->innerJoin('sd.storeDomain', 'sdm')
            ->andWhere('sd.user = :user')
            ->andWhere('sdm.name = :promo')
            ->setParameter('user', $user)
            ->setParameter('promo', 'Promo')
            ->orderBy('sd.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEmailQuoteCustomer($user): array
    {
        $qb = $this->createQueryBuilder('sd');

        $qb->leftJoin('sd.storeDomain', 'sdm')
        ->addSelect('sdm')
        ->andWhere('sd.user = :user')
        ->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('sdm.name'),
                $qb->expr()->neq('sdm.name', ':promo')
            )
        )
        ->setParameter('user', $user)
        ->setParameter('promo', 'Promo')
        ->orderBy('sd.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return EmailQuote[] Returns an array of EmailQuote objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EmailQuote
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
