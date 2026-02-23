<?php

namespace App\Repository;

use App\Entity\ProductType;
use App\Enum\Admin\CacheEnum;
use App\Enum\DBInstanceEnum;
use App\Service\CacheService;
use App\Trait\EntityManagerInstanceTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductType>
 *
 * @method ProductType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductType[]    findAll()
 * @method ProductType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductTypeRepository extends ServiceEntityRepository
{
    use EntityManagerInstanceTrait;

    public function __construct(ManagerRegistry $registry, private readonly CacheService $cacheService)
    {
        $this->setManagerRegistry($registry);
        parent::__construct($registry, ProductType::class);
    }

    public function save(ProductType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function list(): Query
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
        ;
    }

    public function findBySlug(string $slug): ?ProductType
    {
        $cacheKey = $this->cacheService->getProductTypeCacheKey($slug);

        return $this->cacheService->getCached(
            $cacheKey, 
            function () use ($slug) {
                $qb = $this->createQueryBuilder('p', dbInstance: DBInstanceEnum::READER);
                $qb->andWhere($qb->expr()->eq('p.slug', ':slug'));
                $qb->setParameter('slug', $slug);

                return $qb->getQuery()->getOneOrNullResult();
            },
            CacheEnum::PRODUCT_TYPE
        );
    }

    public function getDefaultVariantsBySlug(string $slug): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()?->getDefaultVariants() ?? [];
    }

//    /**
//     * @return ProductType[] Returns an array of ProductType objects
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

//    public function findOneBySomeField($value): ?ProductType
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
