<?php

namespace App\Repository;

use App\Entity\CustomerPhotos;
use App\Enum\Admin\CacheEnum;
use App\Enum\DBInstanceEnum;
use App\Service\CacheService;
use App\Trait\EntityManagerInstanceTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerPhotos>
 *
 * @method CustomerPhotos|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerPhotos|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerPhotos[]    findAll()
 * @method CustomerPhotos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerPhotosRepository extends ServiceEntityRepository
{
    use EntityManagerInstanceTrait;

    public function __construct(ManagerRegistry $registry, private readonly CacheService $cacheService)
    {
        $this->setManagerRegistry($registry);
        parent::__construct($registry, CustomerPhotos::class);
    }

    public function save(CustomerPhotos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CustomerPhotos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getCustomerPhotos($store, bool $isRandom = false, bool $isEnabled = true, int $limit = 50): array
    {
        $cacheKey = sprintf(
            'customer_photos_store_%s_enabled_%s_limit_%d',
            is_object($store) ? $store->getId() : (string) $store,
            $isEnabled ? '1' : '0',
            $limit
        );

        if ($isRandom) {
            $cacheKey .= '_random';
        }

        return $this->cacheService->getCached(
            $cacheKey, 
            function () use ($store, $isEnabled, $limit, $isRandom) {
                $qb = $this->createQueryBuilder('cp', dbInstance: DBInstanceEnum::READER);

                $qb->where('cp.isEnabled = :isEnabled')
                    ->andWhere('cp.store = :store')
                    ->setParameter('isEnabled', $isEnabled)
                    ->setParameter('store', $store)
                    ->setMaxResults($limit)
                    ->orderBy('cp.createdAt', 'DESC');

                $result = $qb->getQuery()->getResult();

                if ($isRandom) {
                    shuffle($result);
                }

                return $result;
            }, 
            CacheEnum::CUSTOMER_PHOTOS
        );
    }

//    /**
//     * @return CustomerPhotos[] Returns an array of CustomerPhotos objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CustomerPhotos
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
