<?php

namespace App\Repository;

use App\Entity\Store;
use App\Entity\StoreDomain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreDomain>
 *
 * @method StoreDomain|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreDomain|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreDomain[]    findAll()
 * @method StoreDomain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreDomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreDomain::class);
    }

    public function save(StoreDomain $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StoreDomain $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findDomainByStoreAndHost(Store $store, string $host)
    {
        $qb = $this->createQueryBuilder('SD');

        $qb->select('SD.id');
        $qb->addSelect('SD.name');
        $qb->addSelect('SD.domain');
        $qb->addSelect('C.name as currencyName');
        $qb->addSelect('C.symbol as currencySymbol');
        $qb->addSelect('C.code as currencyCode');

        $qb->join('SD.currency', 'C');

        $qb->where($qb->expr()->eq('SD.store', ':store'));
        $qb->setParameter('store', $store);

        $qb->andWhere($qb->expr()->eq('SD.domain', ':host'));
        $qb->setParameter('host', $host);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByDomain(string $host): ?StoreDomain
    {
        return $this->createQueryBuilder('sd')
            ->where('sd.domain = :host')
            ->setParameter('host', $host)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return StoreDomain[] Returns an array of StoreDomain objects
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

//    public function findOneBySomeField($value): ?StoreDomain
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
