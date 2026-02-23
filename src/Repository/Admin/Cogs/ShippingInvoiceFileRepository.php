<?php

namespace App\Repository\Admin\Cogs;

use App\Entity\Admin\Cogs\ShippingInvoiceFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingInvoiceFile>
 *
 * @method ShippingInvoiceFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingInvoiceFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingInvoiceFile[]    findAll()
 * @method ShippingInvoiceFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingInvoiceFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingInvoiceFile::class);
    }

//    /**
//     * @return ShippingInvoiceFile[] Returns an array of ShippingInvoiceFile objects
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

//    public function findOneBySomeField($value): ?ShippingInvoiceFile
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
