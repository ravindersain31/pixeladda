<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\SavedPaymentDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SavedPaymentDetail>
 */
class SavedPaymentDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedPaymentDetail::class);
    }

    public function findByUser(AppUser $user): array
    {
        return $this->createQueryBuilder('spd')
            ->select(
                'spd.id',
                'spd.token',
                'spd.type',
                'spd.cardType',
                'spd.last4',
                'spd.expMonth',
                'spd.expYear',
                'spd.isDefault',
                'u.id AS user_id',
                'u.email AS user_email'
            )
            ->join('spd.user', 'u')
            ->andWhere('spd.user = :user')
            ->setParameter('user', $user)
            ->orderBy('spd.isDefault', 'DESC')
            ->addOrderBy('spd.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findNextDefaultForUser(AppUser $user): ?SavedPaymentDetail
    {
        return $this->createQueryBuilder('spd')
            ->andWhere('spd.user = :user')
            ->andWhere('spd.isDefault = false')
            ->setParameter('user', $user)
            ->orderBy('spd.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return SavedPaymentDetail[] Returns an array of SavedPaymentDetail objects
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

    //    public function findOneBySomeField($value): ?SavedPaymentDetail
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
