<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\Referral;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Referral>
 *
 * @method Referral|null find($id, $lockMode = null, $lockVersion = null)
 * @method Referral|null findOneBy(array $criteria, array $orderBy = null)
 * @method Referral[]    findAll()
 * @method Referral[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReferralRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Referral::class);
    }

    public function save(Referral $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Referral $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getReferralCouponsByReferrer(AppUser $referrer): array
    {
        $referralCoupons = $this->createQueryBuilder('r')
            ->leftJoin('r.coupon', 'c') 
            ->leftJoin('r.referred', 'u') 
            ->addSelect('c')
            ->addSelect('u') 
            ->where('r.referrer = :referrer') 
            ->andWhere('r.referred IS NOT NULL')  
            ->setParameter('referrer', $referrer)
            ->getQuery()
            ->getResult(); 

        $result = [];
        foreach ($referralCoupons as $referralCoupon) {
            $result[] = [
                'referralId' => $referralCoupon->getId(),
                'referrerEmail' => $referralCoupon->getReferrer() ? $referralCoupon->getReferrer()->getEmail() : '',
                'referredEmail' => $referralCoupon->getReferred() ? $referralCoupon->getReferred()->getEmail() : '',
                'couponId' => $referralCoupon->getCoupon() ? $referralCoupon->getCoupon()->getId() : '',
                'couponCode' => $referralCoupon->getCoupon() ? $referralCoupon->getCoupon()->getCode() : '',
                'couponUsesTotal' => $referralCoupon->getCoupon() ? $referralCoupon->getCoupon()->getUsesTotal() : 0,
                'couponStore' => $referralCoupon->getCoupon() && $referralCoupon->getCoupon()->getStore() ? $referralCoupon->getCoupon()->getStore()->getShortName() : '',
                'couponStartDate' => $referralCoupon->getCoupon() ? $referralCoupon->getCoupon()->getStartDate() : '',
                'couponEndDate' => $referralCoupon->getCoupon() ? $referralCoupon->getCoupon()->getEndDate() : '',
            ];
        }

        return $result;
    }

    public function getReferredUsers(?\DateTimeImmutable $startDate = null, ?\DateTimeImmutable $endDate = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.referred', 'u')
            ->leftJoin('r.referrer', 'f')
            ->leftJoin('r.coupon', 'c')
            ->addSelect('u', 'f', 'c')
            ->where('r.referred IS NOT NULL');

        if ($startDate) {
            $qb->andWhere('r.createdAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('r.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $qb->orderBy('r.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Referral[] Returns an array of Referral objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Referral
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
