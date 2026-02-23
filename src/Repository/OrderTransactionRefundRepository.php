<?php

namespace App\Repository;

use App\Entity\OrderTransactionRefund;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderTransactionRefund>
 *
 * @method OrderTransactionRefund|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderTransactionRefund|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderTransactionRefund[]    findAll()
 * @method OrderTransactionRefund[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderTransactionRefundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderTransactionRefund::class);
    }

    public function save(OrderTransactionRefund $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderTransactionRefund $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OrderTransactionRefund[] Returns an array of OrderTransactionRefund objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OrderTransactionRefund
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
