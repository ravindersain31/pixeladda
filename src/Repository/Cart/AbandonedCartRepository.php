<?php

namespace App\Repository\Cart;

use App\Entity\Cart\AbandonedCart;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbandonedCart>
 *
 * @method AbandonedCart|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbandonedCart|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbandonedCart[]    findAll()
 * @method AbandonedCart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbandonedCartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbandonedCart::class);
    }

    public function save(AbandonedCart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AbandonedCart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findAbandonedCarts(\DateTimeImmutable $currentDate): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere($qb->expr()->isNull('a.notifiedAt'));
        $qb->andWhere('a.createdAt <= :threshold');
        $qb->setParameter('threshold', $currentDate->modify('-2 hours'));

        return $qb->getQuery()->getResult();
    }

    public function removeAbandonedCartsWithOrders(): void
    {
        $qb = $this->createQueryBuilder('a');

        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(o.cart)')
            ->from(Order::class, 'o')
            ->getDQL();

        $qb->delete(AbandonedCart::class, 'a')
            ->where($qb->expr()->in('a.cart', $subQuery))
            ->getQuery()
            ->execute();
    }

    public function removeAbandonedCartsOlderThan2Days(): void
    {
        $qb = $this->createQueryBuilder('a');

        $qb->delete(AbandonedCart::class, 'a')
            ->where('a.createdAt < :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', new \DateTimeImmutable('-2 days'))
            ->getQuery()
            ->execute();
    }




    //    /**
//     * @return AbandonedCart[] Returns an array of AbandonedCart objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AbandonedCart
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
