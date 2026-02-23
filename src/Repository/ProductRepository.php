<?php

namespace App\Repository;

use App\Constant\CustomSize;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Store;
use App\Enum\Admin\CacheEnum;
use App\Enum\DBInstanceEnum;
use App\Helper\PriceChartHelper;
use App\Service\CacheService;
use App\Trait\EntityManagerInstanceTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    use EntityManagerInstanceTrait;

    public function __construct(ManagerRegistry $registry, private readonly ProductTypeRepository $productTypeRepository, private readonly CacheService $cacheService)
    {
        $this->setManagerRegistry($registry);
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function list(): Query
    {
        $qb = $this->createQueryBuilder('p');
        return $qb->andWhere($qb->expr()->isNull('p.parent'))
            ->andWhere($qb->expr()->isNotNull('p.store'))
            ->andWhere($qb->expr()->isNull('p.deletedAt'))
            ->orderBy('p.id', 'ASC')
            ->getQuery();
    }

    public function findByCategory(Category $category, Store|string|null $store = null, ?array $notSkus = null, ?string $variant = null): Query
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->isNull('p.parent'));
        $qb->andWhere($qb->expr()->isNotNull('p.store'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));

        $qb->leftJoin('p.categories', 'c');
        $qb->andWhere($qb->expr()->eq('p.primaryCategory', ':category'));
        $qb->orWhere($qb->expr()->in('c.slug', [$category->getSlug()]));
        $qb->setParameter('category', $category);

        if ($store) {
            $qb->andWhere($qb->expr()->eq('p.store', ':store'));
            $qb->setParameter('store', $store);
        }

        if ($notSkus) {
            $qb->andWhere($qb->expr()->notIn('p.sku', ':notSkus'));
            $qb->setParameter('notSkus', $notSkus);
        }

        if ($variant) {
            $qb->leftJoin(Product::class, 'pv', 'WITH', 'pv.parent = p.id');
            $qb->andWhere($qb->expr()->eq('pv.name', ':variant'));
            $qb->andWhere($qb->expr()->isNull('pv.deletedAt'));
            $qb->setParameter('variant', $variant);
            $qb->select('DISTINCT p as product, pv.name as variant, pv.image.name as image');
        } else {
            $qb->groupBy('p.id');
        }

        $qb->andWhere($qb->expr()->eq('p.isEnabled', true));
        
        if ($category->getSlug() == 'die-cut') {
            $qb->leftJoin('p.productType', 'pt');
            $qb->addSelect('(CASE WHEN pt.slug = :dieCutSlug THEN 0 ELSE 1 END) AS HIDDEN sortPriority');
            $qb->setParameter('dieCutSlug', $category->getSlug());
            $qb->orderBy('sortPriority', 'ASC');
            $qb->addOrderBy('p.id', 'ASC');
        } else {
            $qb->orderBy('p.id', 'ASC');
        }

        $excludedSkus = ['CUSTOM', 'CUSTOM-SIGN', 'CUSTOM-SIZE', 'DC-CUSTOM', 'BHC-CUSTOM', 'HF-CUSTOM'];
        $qb->andWhere($qb->expr()->notIn('p.sku', ':excludedSkus'));
        $qb->setParameter('excludedSkus', $excludedSkus);

        return $qb->getQuery();
    }

    public function findImageUrlOfProductVariant(Product $product, string $variant): ?array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.image.name as image');
        $qb->andWhere($qb->expr()->eq('p.parent', ':parent'));
        $qb->andWhere($qb->expr()->eq('p.name', ':variant'));
        $qb->setParameter('parent', $product);
        $qb->setParameter('variant', $variant);
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function filterByCategories(array $categories = [], array $subCategories = []): Query
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->isNull('p.parent'));
        $qb->andWhere($qb->expr()->isNotNull('p.store'));
        $qb->andWhere($qb->expr()->eq('p.isEnabled', ':isEnabled'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));

        if (count($categories) > 0) {
            $qb->leftJoin('p.primaryCategory', 'c');
            $qb->leftJoin('p.categories', 'pc');

            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('c.slug', ':categories'),
                    $qb->expr()->andX(
                        $qb->expr()->in('pc.slug', ':categories'),
                        $qb->expr()->eq('pc.isEnabled', ':isEnabled')
                    )
                )
            );

            $qb->setParameter('categories', $categories);

            if (count($subCategories) > 0) {

                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->in('c.slug', ':subCategories'),
                        $qb->expr()->andX(
                            $qb->expr()->in('pc.slug', ':subCategories'),
                            $qb->expr()->eq('pc.isEnabled', ':isEnabled')
                        )
                    )
                );

                $qb->setParameter('subCategories', $subCategories);
            }
        }
        $qb->setParameter('isEnabled', true);

        if (in_array('die-cut', $categories, true) || in_array('die-cut', $subCategories, true)) {
            $qb->leftJoin('p.productType', 'pt');
            $qb->addSelect('(CASE WHEN pt.slug = :dieCutSlug THEN 0 ELSE 1 END) AS HIDDEN sortPriority');
            $qb->setParameter('dieCutSlug', 'die-cut');
            $qb->orderBy('sortPriority', 'ASC');
            $qb->addOrderBy('p.id', 'ASC');
        } else {
            $qb->orderBy('p.id', 'ASC');
        }

        return $qb->getQuery();
    }

    public function filterByCategoriesIds(array $ids = []): Query
    {
        $qb = $this->createQueryBuilder('p');
        $qb->addSelect("
            CASE WHEN c.slug = 'die-cut' THEN 0 ELSE 1 END AS HIDDEN priority
        ");
        $qb->andWhere($qb->expr()->isNull('p.parent'));
        $qb->andWhere($qb->expr()->isNotNull('p.store'));
        $qb->andWhere($qb->expr()->eq('p.isEnabled', ':isEnabled'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));

        if (count($ids) > 0) {
            $qb->leftJoin('p.primaryCategory', 'c');
            $qb->leftJoin('p.categories', 'pc');

            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('c.id', ':categories'),
                    $qb->expr()->andX(
                        $qb->expr()->in('pc.id', ':categories'),
                        $qb->expr()->eq('pc.isEnabled', ':isEnabled')
                    )
                )
            );

            $qb->setParameter('categories', $ids);
        }

        $excludedSkus = ['BHC-CUSTOM', 'HF-CUSTOM'];
        $qb->andWhere($qb->expr()->notIn('p.sku', ':excludedSkus'));
        $qb->setParameter('excludedSkus', $excludedSkus);

        $qb->setParameter('isEnabled', true);
        $qb->addOrderBy('priority', 'ASC');
        $qb->addOrderBy('p.id', 'ASC');
        return $qb->getQuery();
    }

    public function getLastProductSku(?Product $parentProduct = null, ?Category $category = null)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.sku');
        if ($parentProduct) {
            $qb->andWhere($qb->expr()->eq('p.parent', ':parent'));
            $qb->setParameter('parent', $parentProduct);
        } else {
            $qb->andWhere($qb->expr()->isNull('p.parent'));
        }

        if ($category) {
            $qb->leftJoin('p.primaryCategory', 'pc');
            $qb->andWhere($qb->expr()->eq('pc', ':category'));
            $qb->setParameter('category', $category);
        }

        $qb->orderBy('p.id', 'DESC');
        // $qb->andWhere($qb->expr()->isNull('p.deletedAt'));
        $qb->setMaxResults(1);
        $query = $qb->getQuery();
        $result = $query->getResult();
        if (isset($result[0])) {
            return $result[0]['sku'];
        }
        return 0;
    }

    public function isSkuExists(string $sku, ?Product $parentProduct = null): bool
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.sku');
        if ($parentProduct) {
            $qb->andWhere($qb->expr()->eq('p.parent', ':parent'));
            $qb->setParameter('parent', $parentProduct);
        } else {
            $qb->andWhere($qb->expr()->isNull('p.parent'));
        }
        $qb->andWhere($qb->expr()->eq('p.sku', ':sku'));
        $qb->setParameter('sku', $sku);
        // $qb->andWhere($qb->expr()->isNull('p.deletedAt'));
        $result = $qb->getQuery()->getResult();
        return count($result) > 0;
    }

    public function findProduct(string $categorySlug, string $productTypeSlug, string $sku, string|int|null $store = null)
    {

        $qb = $this->createQueryBuilder('p');

        $qb->andWhere($qb->expr()->isNotNull('p.store'));
        $qb->andWhere($qb->expr()->isNull('p.parent'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));

        $qb->leftJoin('p.primaryCategory', 'pc');
        $qb->andWhere($qb->expr()->eq('pc.slug', ':categorySlug'));
        $qb->setParameter('categorySlug', $categorySlug);

        $qb->leftJoin('p.productType', 'pt');
        $qb->andWhere($qb->expr()->eq('pt.slug', ':productTypeSlug'));
        $qb->setParameter('productTypeSlug', $productTypeSlug);

        $qb->andWhere($qb->expr()->eq('p.sku', ':sku'));
        $qb->setParameter('sku', $sku);

        if ($store) {
            $qb->andWhere($qb->expr()->eq('p.store', ':store'));
            $qb->setParameter('store', $store);
        }

        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function isVariantExists(Product $product, $variant): bool
    {
        $qb = $this->createQueryBuilder('P');
        $qb->join('P.variants', 'PV');
        $qb->andWhere($qb->expr()->eq('PV.slug', ':variant'));
        $qb->setParameter('variant', $variant);
        $qb->andWhere($qb->expr()->eq('PV.parent', ':product'));
        $qb->setParameter('product', $product);

        $qb->setMaxResults(1);
        return !is_null($qb->getQuery()->getOneOrNullResult());
    }

    public function findProductsBySku(string|array $skus, $limit = null): array
    {
        if (is_string($skus)) {
            $skus = array_map('trim', explode(',', $skus));
        }

        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->isNotNull('p.store'));
        $qb->andWhere($qb->expr()->isNull('p.parent'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));
        $qb->andWhere($qb->expr()->in('p.sku', ':skus'));
        $qb->setParameter('skus', $skus);
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    public function findProductsForHomeBySku(string|array $skus, $limit = null): array
    {
        if (is_string($skus)) {
            $skus = array_map('trim', explode(',', $skus));
        }

        $qb = $this->createQueryBuilder('p');

        $qb->select('p.id');
        $qb->addSelect('p.sku');
        $qb->addSelect('p.name');
        $qb->addSelect('p.slug');
        $qb->addSelect('pr.sku as parentSku');
        $qb->addSelect('pc.name as categoryName');
        $qb->addSelect('pc.slug as categorySlug');
        $qb->addSelect('pt.name as productTypeName');
        $qb->addSelect('pt.slug as productTypeSlug');
        $qb->addSelect('p.seoImage.name as seoImageName');
        $qb->addSelect('p.displayImage.name as displayImageName');
        $qb->addSelect('p.image.name as imageName');
        $qb->addSelect('pt.pricing as productTypePricing');
        $qb->addSelect('p.pricing as productPricing');

        $qb->leftJoin('p.parent', 'pr');
        $qb->leftJoin('p.primaryCategory', 'pc');
        $qb->leftJoin('p.productType', 'pt');

        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));
        $qb->andWhere($qb->expr()->in('p.sku', ':skus'));

        $qb->orderBy('p.sortPosition', 'ASC');
        $qb->setParameter('skus', $skus);
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    public function findProductsForHomeBySkus(string|array $skus, $limit = null): array
    {
        $cacheKey = sprintf(
            'find_products_for_home_by_skus:%s:%s',
            is_array($skus) ? implode(',', $skus) : $skus,
            $limit ?? 'no-limit'
        );

        return $this->cacheService->getCached(
            $cacheKey,
            function () use ($skus, $limit) {
                $qb = $this->createQueryBuilder('p', dbInstance: DBInstanceEnum::READER);

                $qb->select('p.id', 'p.sku', 'p.name', 'p.slug', 'pr.sku as parentSku', 'pc.name as categoryName', 'pc.slug as categorySlug', 'pt.name as productTypeName', 'pt.slug as productTypeSlug', 'p.seoImage.name as seoImageName', 'p.displayImage.name as displayImageName', 'p.image.name as imageName', 'pt.pricing as productTypePricing', 'pt.customPricing as productTypeCustomPricing', 'p.pricing as productPricing')
                    ->leftJoin('p.parent', 'pr')
                    ->leftJoin('p.primaryCategory', 'pc')
                    ->leftJoin('p.productType', 'pt')
                    ->andWhere($qb->expr()->isNull('p.deletedAt'))
                    ->andWhere(is_array($skus) ? $qb->expr()->in('p.sku', ':skus') : $qb->expr()->eq('p.sku', ':skus'))
                    ->orderBy('p.sortPosition', 'ASC')
                    ->setParameter('skus', $skus);

                if ($limit) {
                    $qb->setMaxResults($limit);
                }

                return $qb->getQuery()->getResult();
            }, 
            CacheEnum::PRODUCT
        );
    }


    public function searchProduct(string $query, string|null $category = null, bool $checkEnabled = true, int $offset = 0, int $limit = 100, bool $result = true)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->isNotNull('p.store'));
        $qb->andWhere($qb->expr()->isNull('p.parent'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));

        if ($checkEnabled) {
            $qb->andWhere($qb->expr()->eq('p.isEnabled', ':isEnabled'));
            $qb->setParameter('isEnabled', true);
        }

        if ($category) {
            $qb->andWhere($qb->expr()->eq('p.primaryCategory', ':category'));

            $qb->leftJoin('p.categories', 'pc');
            $qb->orWhere($qb->expr()->eq('pc', $category));

            $qb->setParameter('category', $category);
        }

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('p.name', ':query'),
            $qb->expr()->like('p.sku', ':query')
        ));

        $qb->setParameter('query', '%' . $query . '%');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        if (!$result) {
            return $qb->getQuery();
        }

        // $qb->setMaxResults(40);
        return $qb->getQuery()->getResult();
    }

    public function updatePosition(string $variantName, int $sortPosition)
    {
        $qb = $this->createQueryBuilder('P');

        $qb->set('P.sortPosition', ':sortPosition');
        $qb->setParameter('sortPosition', $sortPosition);

        $qb->andWhere($qb->expr()->eq('P.name', ':variantName'));
        $qb->setParameter('variantName', $variantName);

        $qb->andWhere($qb->expr()->isNotNull('P.parent'));

        $qb->update();

        return $qb->getQuery()->execute();
    }

    public function updatePrePackedVariant(string $newVariantName)
    {
        $subQb = $this->createQueryBuilder('P');
        $subQb->select('P.id')
            ->join('P.parent', 'parent')
            ->join('parent.productType', 'PT')
            ->where($subQb->expr()->eq('PT.slug', ':prePackedSlug'))
            ->andWhere($subQb->expr()->isNotNull('P.parent'))
            ->setParameter('prePackedSlug', 'yard-letters');

        $productIds = $subQb->getQuery()->getScalarResult();

        if (!empty($productIds)) {
            $qb = $this->createQueryBuilder('P');
            $qb->update()
                ->set('P.name', ':newVariantName')
                ->where($qb->expr()->in('P.id', ':productIds'))
                ->setParameter('newVariantName', $newVariantName)
                ->setParameter('productIds', array_column($productIds, 'id'));
            return $qb->getQuery()->execute();
        }

        return 0;
    }

    public function findActiveVariants(Product $product)
    {
        $qb = $this->createQueryBuilder('P');

        $qb->orderBy('P.sortPosition', 'ASC');

        $qb->andWhere($qb->expr()->eq('P.parent', ':parent'));
        $qb->setParameter('parent', $product);

        $qb->andWhere($qb->expr()->isNull('P.deletedAt'));

        $qb->orderBy('P.sortPosition', 'ASC');

        return $qb->getQuery()->getResult();
    }


    public function findOneProductPerCategory(array $categoryIds): array
    {
        $subQuery = $this->createQueryBuilder('CP');
        $subQuery->select('MIN(CP.id)');
        $subQuery->andWhere($subQuery->expr()->isNull('P.parent'));
        $subQuery->andWhere($subQuery->expr()->isNotNull('P.store'));
        $subQuery->andWhere($subQuery->expr()->isNull('P.deletedAt'));
        $subQuery->andWhere($subQuery->expr()->eq('P.isEnabled', ':isEnabled'));

        $subQuery->andWhere($subQuery->expr()->in('CP.primaryCategory', ':categoryIds'));
        $subQuery->groupBy('CP.primaryCategory');

        $qb = $this->createQueryBuilder('P');
        $qb->leftJoin('P.variants', 'PV');
        $qb->andWhere($qb->expr()->in('P.id', $subQuery->getDQL()));

        $qb->setParameter('categoryIds', $categoryIds);
        $qb->setParameter('isEnabled', true);

        return $qb->getQuery()->getResult();
    }


    public function productBySearch(array $queries = [], int $limit = 40): array
    {

        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->isNotNull('p.store'))
            ->andWhere($qb->expr()->isNull('p.parent'))
            ->andWhere($qb->expr()->isNull('p.deletedAt'))
            ->andWhere($qb->expr()->eq('p.isEnabled', ':isEnabled'))
            ->setParameter('isEnabled', true)
            ->leftJoin('p.primaryCategory', 'pc');

        if (!empty($queries)) {
            $orX = $qb->expr()->orX();

            foreach ($queries as $index => $query) {
                $query = strtolower($query);
                $orX->add(
                    $qb->expr()->orX(
                        $qb->expr()->like('LOWER(p.name)', ":query_" . $index),
                        $qb->expr()->like('LOWER(p.sku)', ":query_" . $index),
                        $qb->expr()->like('LOWER(p.seoMeta)', ":query_" . $index),
                        $qb->expr()->like('LOWER(pc.slug)', ":query_" . $index),
                        $qb->expr()->like('LOWER(pc.name)', ":query_" . $index)
                    )
                );
                $qb->setParameter('query_' . $index, "%" . $query . "%");
            }

            if (!empty($queries)) {
                $qb->andWhere($orX);
            }
        }

        $results = $qb->getQuery()->getResult();

        if (count($results) <= $limit) {
            $results = $this->retrieveAllProducts();
        }

        // Filter and score the results
        $filteredResults = array_map(function (Product $product) use ($queries) {
            $score = $this->calculateProductScore($product, $queries);
            return ['product' => $product, 'score' => $score];
        }, $results);

        if (!empty($queries)) {
            $filteredResults = array_filter($filteredResults, fn($item) => $item['score'] > 0);
        }

        // Sort and return the top results
        usort($filteredResults, fn($a, $b) => $b['score'] <=> $a['score']);
        $topResults = array_slice($filteredResults, 0);

        // Extract the products from the scored results
        return array_map(fn($item) => $item['product'], $topResults);
    }

    private function retrieveAllProducts(): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->isNotNull('p.store'))
            ->andWhere($qb->expr()->isNull('p.parent'))
            ->andWhere($qb->expr()->isNull('p.deletedAt'))
            ->andWhere($qb->expr()->eq('p.isEnabled', ':isEnabled'))
            ->setParameter('isEnabled', true)
            ->leftJoin('p.primaryCategory', 'pc');

        return $qb->getQuery()->getResult();
    }

    private function calculateProductScore(Product $product, array $queries): float
    {
        $score = 0;
        $productName = $product->getName();
        $categoryName = $product->getPrimaryCategory() ? $product->getPrimaryCategory()->getName() : '';
        $sku = $product->getSku();
        $slug = $product->getSlug();

        foreach ($queries as $query) {
            $score += $this->calculateExactMatchScore($product, $query);
            $score += $this->calculateSimilarityScore($productName, $query);
            $score += $this->calculateSimilarityScore($categoryName, $query);
            $score += $this->calculateSimilarityScore($sku, $query);
            $score += $this->calculateSimilarityScore($slug, $query);
        }

        return floatval($score);
    }

    private function calculateExactMatchScore(Product $product, string $query): float
    {
        $score = 0;
        $seoMeta = $product->getSeoMeta();
        if ($seoMeta) {
            if (isset($seoMeta['title']) && stripos($seoMeta['title'], $query) !== false) {
                $score += 5;
            }
            if (isset($seoMeta['keywords']) && stripos($seoMeta['keywords'], $query) !== false) {
                $score += 5;
            }
        }
        return floatval($score);
    }

    private function calculateSimilarityScore(string $text, string $query): float
    {
        $score = 0;
        $tokens = explode(' ', strtolower($text));
        $queryTokens = explode(' ', strtolower($query));

        // Token-based similarity
        $commonTokens = array_intersect($tokens, $queryTokens);
        $similarity = count($commonTokens) / max(count($tokens), count($queryTokens));
        if ($similarity > 0.3) { // Adjust the threshold as needed
            $score += $similarity * 10; // Scale the similarity score as needed
        }

        // Fuzzy search for typos using Levenshtein distance
        if (strlen($query) > 0) { // Ensure $query is not empty
            foreach ($tokens as $token) {
                $levenshteinDistance = levenshtein(strtolower($token), strtolower($query));
                $maxLen = max(strlen($token), strlen($query));
                $levenshteinSimilarity = 1 - ($levenshteinDistance / $maxLen);
                if ($levenshteinSimilarity > 0.5) { // Adjust the threshold as needed
                    $score += $levenshteinSimilarity * 5; // Scale the similarity score as needed
                }
            }
        }

        return floatval($score);
    }

    public function findVariantByParentAndName($wireStakeProduct, $name)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->eq('p.parent', ':parent'));
        $qb->andWhere($qb->expr()->eq('p.name', ':name'));
        $qb->andWhere($qb->expr()->isNull('p.deletedAt'));
        $qb->setParameter('parent', $wireStakeProduct);
        $qb->setParameter('name', $name);
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    //    /**
//     * @return Product[] Returns an array of Product objects
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

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
