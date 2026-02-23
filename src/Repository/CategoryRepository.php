<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Store;
use App\Enum\Admin\CacheEnum;
use App\Enum\DBInstanceEnum;
use App\Service\StoreInfoService;
use App\Service\CacheService;
use App\Trait\EntityManagerInstanceTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    use EntityManagerInstanceTrait;
    private readonly StoreInfoService $storeInfoService;

    public function __construct(ManagerRegistry $registry, StoreInfoService $storeInfoService, private readonly CacheService $cacheService)
    {
        $this->setManagerRegistry($registry);
        $this->storeInfoService = $storeInfoService;
        parent::__construct($registry, Category::class);
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findCategory(string $slug)
    {
        $cacheKey = $this->cacheService->getCategoryCacheKey($slug, 'category_slug');

        return $this->cacheService->getCached(
            $cacheKey,
            function () use ($slug) {
                $qb = $this->createQueryBuilder('c', dbInstance: DBInstanceEnum::READER);
                $qb->where($qb->expr()->isNull('c.parent'));
                $qb->andWhere($qb->expr()->eq('c.slug', ':slug'));
                $qb->setParameter('slug', $slug);
                return $qb->getQuery()->getOneOrNullResult();
            },
            CacheEnum::CATEGORY
        );
    }

    public function findSubCategory(string $slug)
    {
       $cacheKey = $this->cacheService->getCategoryCacheKey($slug, 'subcategory_slug');

        return $this->cacheService->getCached(
            $cacheKey,
            function () use ($slug) {
                $qb = $this->createQueryBuilder('c', dbInstance: DBInstanceEnum::READER);
                $qb->where($qb->expr()->isNotNull('c.parent'));
                $qb->andWhere($qb->expr()->eq('c.slug', ':slug'));
                $qb->setParameter('slug', $slug);
                return $qb->getQuery()->getOneOrNullResult();
            },
            CacheEnum::CATEGORY
        );
    }

    public function list(): Query
    {
        $qb = $this->createQueryBuilder('c', dbInstance: DBInstanceEnum::READER);
        $qb->where($qb->expr()->isNull('c.parent'));
        $qb->orderBy('c.displayInMenu', 'ASC');
        $qb->OrderBy('c.sortPosition', 'ASC');
        return $qb->getQuery();
    }

    public function getCategoryHasProducts(?string $storeId = null, bool $displayInMenu = false): array
    {
        $cacheKey = sprintf(
            'category_has_products_%s_%s',
            $storeId ?? 'all',
            $displayInMenu ? 'menu' : 'all'
        );

        return $this->cacheService->getCached(
            $cacheKey, 
            function () use ($storeId, $displayInMenu) {
                $qb = $this->createQueryBuilder(alias: 'C', dbInstance: DBInstanceEnum::READER);
                $qb->addSelect('COUNT(P) as HIDDEN productCount, COUNT(P1) as HIDDEN productCount1');
                $qb->leftJoin('C.primaryProducts', 'P', 'WITH', 'P.isEnabled = true');
                $qb->leftJoin('C.products', 'P1', 'WITH', 'P1.isEnabled = true and P1.primaryCategory = C.parent');
                $qb->having($qb->expr()->gt('COUNT(DISTINCT P.id)', 0));
                $qb->orHaving($qb->expr()->gt('COUNT(DISTINCT P1.id)', 0));
                $qb->orHaving($qb->expr()->in('C.slug', ':includeCategories'));
                $qb->groupBy('C.id');

                $qb->andWhere($qb->expr()->eq('C.isEnabled', ':isEnabled'));
                $qb->andWhere($qb->expr()->isNull('C.parent'));
                $qb->setParameter('isEnabled', true);
                $qb->setParameter('includeCategories', ['yard-letters', 'die-cut', 'big-head-cutouts', 'hand-fans', 'custom-signs']);

                if ($storeId) {
                    $qb->andWhere($qb->expr()->eq('C.store', ':store'));
                    $qb->setParameter('store', $storeId);
                }

                if ($displayInMenu) {
                    $qb->andWhere($qb->expr()->eq('C.displayInMenu', ':displayInMenu'));
                    $qb->setParameter('displayInMenu', true);
                }

                $qb->orderBy('C.sortPosition', 'ASC');

                return $qb->getQuery()->getResult();
            }, 
            CacheEnum::CATEGORY
        ); 
    }

    public function getCategoryHasProductsSelective(?string $storeId = null, ?bool $displayInMenu = false, $showAll = false, ?string $orderBy = 'latest',): array
    {
        $cacheKey = sprintf(
            'category_has_products_selective_store_%s_menu_%s_all_%s',
            $storeId ?? 'null',
            $displayInMenu ? '1' : '0',
            $showAll ? '1' : '0',
            $orderBy ?? 'latest',
        );

        return $this->cacheService->getCached(
            $cacheKey, 
            function () use ($storeId, $displayInMenu, $showAll, $orderBy) {
                $qb = $this->createQueryBuilder(alias: 'C', dbInstance: DBInstanceEnum::READER);
                $qb->select('C.id, C.name, C.slug, P.slug AS parentSlug, 
                        COUNT(DISTINCT P1.id) AS productCount1, 
                        COUNT(DISTINCT P2.id) AS productCount2,  
                        C.categoryThumbnail.name AS categoryThumbnailName,
                        C.thumbnail.name AS thumbnailName,
                        C.isEnabled, C.displayInMenu,
                        C.promoCategoryThumbnail.name AS promoCategoryThumbnailName,
                        C.promoThumbnail.name AS promoThumbnailName
                    ');

                $qb->leftJoin('C.parent', 'P');
                $qb->leftJoin('C.primaryProducts', 'P1', 'WITH', 'P1.isEnabled = true or (C.slug IN (:includeCategories) AND P1.isEnabled = true)');
                $qb->leftJoin('C.products', 'P2', 'WITH', 'P2.isEnabled = true and P2.primaryCategory = C.parent or (C.slug IN (:includeCategories) AND P2.isEnabled = true)');

                $qb->andWhere($qb->expr()->eq('C.isEnabled', ':isEnabled'));
                $qb->setParameter('isEnabled', true);

                if ($storeId) {
                    $qb->andWhere($qb->expr()->eq('C.store', ':store'));
                    $qb->setParameter('store', $storeId);
                }

                if (!is_null($displayInMenu)) {
                    $qb->andWhere($qb->expr()->eq('C.displayInMenu', ':displayInMenu'));
                    $qb->setParameter('displayInMenu', $displayInMenu);
                }

                if (!$showAll) {
                    $qb->andWhere($qb->expr()->isNull('C.parent'));
                }

                // Include the relevant fields in the GROUP BY clause
                $qb->groupBy('C.id, C.name, C.slug, C.categoryThumbnail.name, C.thumbnail.name');

                $qb->having($qb->expr()->gt('COUNT(DISTINCT P1.id)', 0));
                $qb->orHaving($qb->expr()->gt('COUNT(DISTINCT P2.id)', 0));
                $qb->orHaving($qb->expr()->in('C.slug', ':includeCategories'));

                $qb->setParameter('includeCategories', ['yard-letters', 'die-cut', 'big-head-cutouts', 'hand-fans', 'custom-signs']);
                if ($orderBy === 'latest') {
                    $qb->orderBy('C.createdAt', 'DESC');
                } else {
                    $qb->orderBy('C.sortPosition', 'ASC');
                }

                $query = $qb->getQuery();

                $categories = $query->getArrayResult();

                return $this->transformCategories($categories);
            },
            CacheEnum::CATEGORY
        ); 
    }

    private function transformCategories(array $categories): array
    {
        $isPromoStore = $this->storeInfoService->StoreInfo()['isPromoStore'] ?? false;
        return array_map(function ($category) use ($isPromoStore) {
            return [
                    'categoryThumbnail' => $this->generateImageUrl($category['categoryThumbnailName']),
                    'thumbnail' => $this->generateImageUrl($category['thumbnailName']),
                    'promoCategoryThumbnail' => $isPromoStore ? $this->generateImageUrl($category['promoCategoryThumbnailName']) : null,
                    'promoThumbnail' => $isPromoStore ? $this->generateImageUrl($category['promoThumbnailName']) : null,
                ] + $category;
        }, $categories);
    }

    private function generateImageUrl(?string $imageName): ?string
    {
        return $imageName ? 'https://static.yardsignplus.com/category/' . $imageName : null;
    }


    public function findByStoreAndSelect(Store|string|int $store, string $select = 'C', bool $displayInMenu = true): Query
    {
        $qb = $this->createQueryBuilder('C');
        $qb->select($select);

        $qb->andWhere($qb->expr()->eq('C.isEnabled', ':isEnabled'));
        $qb->setParameter('isEnabled', true);

        $qb->andWhere($qb->expr()->eq('C.store', ':store'));
        $qb->setParameter('store', $store);

        $qb->andWhere($qb->expr()->eq('C.displayInMenu', ':displayInMenu'));
        $qb->setParameter('displayInMenu', $displayInMenu);

        $qb->orderBy('C.sortPosition', 'ASC');

        return $qb->getQuery();
    }

    public function getNewArrivalCategories(?string $storeId = null, int $days = 90): array
    {
        $date = (new \DateTime())->modify("-{$days} days");

        $qb = $this->createQueryBuilder('c');

        $qb->select(
            'c.id, c.name, c.slug,
            COUNT(DISTINCT P1.id) AS productCount1,
            c.categoryThumbnail.name AS categoryThumbnailName,
            c.thumbnail.name AS thumbnailName,
            c.isEnabled, c.displayInMenu,
            c.promoCategoryThumbnail.name AS promoCategoryThumbnailName,
            c.promoThumbnail.name AS promoThumbnailName'
        )
        ->leftJoin('c.primaryProducts', 'P1', 'WITH', 'P1.isEnabled = true')
        ->andWhere('c.createdAt >= :date')
        ->andWhere('c.isEnabled = :isEnabled')
        ->andWhere('c.parent IS NULL')
        ->setParameter('date', $date)
        ->setParameter('isEnabled', true);

        if ($storeId) {
            $qb->andWhere($qb->expr()->eq('c.store', ':store'));
            $qb->setParameter('store', $storeId);
        }

        $qb->groupBy('c.id, c.name, c.slug, c.categoryThumbnail.name, c.thumbnail.name')
        ->having($qb->expr()->gt('COUNT(DISTINCT P1.id)', 0))
        ->orderBy('c.createdAt', 'DESC');

        $newCategories = $this->transformCategories($qb->getQuery()->getResult());
        
        return $newCategories;
    }

    public function findBySlugAndStore(string $slug, ?string $storeId): ?Category
    {
        return $this->createQueryBuilder('C')
            ->andWhere('C.slug = :slug')
            ->andWhere('C.store = :store')
            ->setParameter('slug', $slug)
            ->setParameter('store', $storeId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Category[] Returns an array of Category objects
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

//    public function findOneBySomeField($value): ?Category
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
