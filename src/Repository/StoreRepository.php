<?php

namespace App\Repository;

use App\Entity\Store;
use App\Enum\Admin\CacheEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use App\Enum\DBInstanceEnum;
use App\Service\CacheService;
use App\Trait\EntityManagerInstanceTrait;

/**
 * @extends ServiceEntityRepository<Store>
 *
 * @method Store|null find($id, $lockMode = null, $lockVersion = null)
 * @method Store|null findOneBy(array $criteria, array $orderBy = null)
 * @method Store[]    findAll()
 * @method Store[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreRepository extends ServiceEntityRepository
{

    use EntityManagerInstanceTrait;
    public function __construct(ManagerRegistry $registry, private readonly CacheService $cacheService)
    {
        parent::__construct($registry, Store::class);
        $this->setManagerRegistry($registry);
    }

    public function save(Store $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Store $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function listStore(): Query
    {
        $qb = $this->createQueryBuilder('S');

        return $qb->getQuery();
    }

    public function getStoreByHost(string $host): ?array
    {
        $cacheKey = $this->cacheService->getStoreCacheKey($host);

        return $this->cacheService->getCached(
            $cacheKey,
            function () use ($host) {
                $qb = $this->createQueryBuilder('S', dbInstance: DBInstanceEnum::READER);

                $qb->select('S.id');
                $qb->addSelect('S.name');
                $qb->addSelect('S.shortName');

                $qb->addSelect('SD.id as domainId');
                $qb->addSelect('SD.name as domainName');
                $qb->addSelect('SD.domain as domain');

                $qb->addSelect('C.id as currencyId');
                $qb->addSelect('C.name as currencyName');
                $qb->addSelect('C.symbol as currencySymbol');
                $qb->addSelect('C.code as currencyCode');

                $qb->innerJoin('S.storeDomains', 'SD');
                $qb->innerJoin('SD.currency', 'C');

                $qb->where($qb->expr()->in(':host', 'SD.domain'));
                $qb->setParameter('host', $host);

                $qb->setMaxResults(1);

                return $qb->getQuery()->getOneOrNullResult();
            },
            CacheEnum::STORE
        );
    }

    public function findAllByShortName(array $shortNames)
    {
        $qb = $this->createQueryBuilder('S');
        $qb->andWhere($qb->expr()->in('S.shortName', ':shortNames'));
        $qb->setParameter('shortNames', $shortNames);
        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Store[] Returns an array of Store objects
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

//    public function findOneBySomeField($value): ?Store
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
