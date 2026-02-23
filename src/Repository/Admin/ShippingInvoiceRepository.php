<?php

namespace App\Repository\Admin;

use App\Entity\Admin\ShippingInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingInvoice>
 *
 * @method ShippingInvoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingInvoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingInvoice[]    findAll()
 * @method ShippingInvoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingInvoice::class);
    }

//    /**
//     * @return ShippingInvoice[] Returns an array of ShippingInvoice objects
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

//    public function findOneBySomeField($value): ?ShippingInvoice
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
