<?php

namespace App\Repository\Admin;

use App\Entity\Admin\Coupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coupon>
 *
 * @method Coupon|null find($id, $lockMode = null, $lockVersion = null)
 * @method Coupon|null findOneBy(array $criteria, array $orderBy = null)
 * @method Coupon[]    findAll()
 * @method Coupon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    public function save(Coupon $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Coupon $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findCouponByCode(string $code): ?Coupon
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.code = :code')
            ->andWhere('c.isEnabled = :isEnabled')
            ->andWhere('c.endDate > :now')
            ->andWhere('c.usesTotal > 0')
            ->setParameter('code', $code)
            ->setParameter('isEnabled', true)
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getMaxDiscountedCoupons(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c.code, c.discount, c.type, c.maximumDiscount, c.minimumQuantity, c.maximumQuantity');

        $qb->where('c.store = :store')
            ->setParameter('store', 1);

        $qb->andWhere($qb->expr()->eq('c.isEnabled', ':isEnabled'));
        $qb->setParameter('isEnabled', true);

        $qb->andWhere($qb->expr()->eq('c.isPromotional', ':isPromotional'));
        $qb->setParameter('isPromotional', true);

        // $qb->andWhere($qb->expr()->eq('c.type', ':type'));
        // $qb->setParameter('type', 'P');

        $qb->andWhere($qb->expr()->isNull('c.couponType'));
        // $qb->andWhere($qb->expr()->isNotNull('c.maximumDiscount'));

        return $qb->getQuery()->getArrayResult();
    }

    public function findAllRegularCoupons(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.couponType IS NULL OR c.couponType = :blank')
            ->setParameter('blank', '');

        return $qb->getQuery()->getResult();
    }

    public function findAllEnabledCoupons(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.couponType IS NULL OR c.couponType = :blank')
            ->andWhere('c.isEnabled = :enabled')
            ->setParameter('blank', '')
            ->setParameter('enabled', true);

        return $qb->getQuery()->getResult();
    }

    public function getManuallyGroupedCoupons(): array
    {
        $couponMap = [
            'Home page banner — min qty, capped' => [
                'SAVE10',
                'BULK12SAVE',
                'GRAND15SAVE',
                'ULTRA20SAVE',
            ],
            'No min qty, capped — CSR discretion' => [
                'SAVE15NOW',
                'SUPER20',
            ],
            'No cap — large qty orders, CSR discretion' => [
                'WHOLESALE20BULK',
                'WHOLESALE25BULK',
            ],
        ];

        $coupons = $this->createQueryBuilder('c')
            ->where('c.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();

        $grouped = [];

        foreach ($couponMap as $section => $codes) {
            $grouped[$section] = [];
        }

        foreach ($coupons as $coupon) {
            /** @var Coupon $coupon */
            $code = $coupon->getCode();

            foreach ($couponMap as $section => $allowedCodes) {
                if (in_array($code, $allowedCodes, true)) {
                    $grouped[$section][] = [
                        'code'            => $coupon->getCode(),
                        'discount'        => $coupon->getDiscount(),
                        'type'            => $coupon->getType(),
                        'minQty'          => $coupon->getMinimumQuantity(),
                        'maxQty'          => $coupon->getMaximumQuantity(),
                        'maxDiscount'     => $coupon->getMaximumDiscount(),
                    ];
                }
            }
        }

        return $grouped;
    }
}
