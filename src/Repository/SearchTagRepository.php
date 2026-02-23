<?php

namespace App\Repository;

use App\Entity\SearchTag;
use App\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SearchTag>
 *
 * @method SearchTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearchTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearchTag[]    findAll()
 * @method SearchTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchTag::class);
    }

    public function save(SearchTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SearchTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function redirectUrlByTag(string $tag, Store $store): ?SearchTag
    {
        $result = $this->createQueryBuilder('s')
            ->where('s.store = :store')
            ->setParameter('store', $store)
            ->select('s')
            ->getQuery()
            ->getResult();

        foreach ($result as $searchTag) {
            if (in_array($tag, $searchTag->getTags())) {
                return $searchTag;
            }
        }

        return null;
    }

//    /**
//     * @return SearchTag[] Returns an array of SearchTag objects
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

//    public function findOneBySomeField($value): ?SearchTag
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
