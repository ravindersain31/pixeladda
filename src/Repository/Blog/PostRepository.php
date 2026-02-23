<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function save(Post $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Post $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function list(): Query
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'ASC')
            ->getQuery();
    }

    public function findByStore($store): Query
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->eq('p.store', ':store'));
        $qb->setParameter('store', $store);
        $qb->andWhere($qb->expr()->eq('p.enabled', ':enabled'));
        $qb->setParameter('enabled', true);
        $qb->orderBy('p.publishedAt', 'DESC');
        return $qb->getQuery();
    }

    public function findByCategory(Category $category, $store)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->eq('p.store', ':store'));
        $qb->setParameter('store', $store);
        $qb->andWhere(':category MEMBER OF p.categories');
        $qb->setParameter('category', $category);
        $qb->andWhere($qb->expr()->eq('p.enabled', ':enabled'));
        $qb->setParameter('enabled', true);
        $qb->orderBy('p.publishedAt', 'DESC');
        return $qb->getQuery();

    }

    public function findByCategoryForStore(Post $post, Category $category, $store, $limit = 5)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere($qb->expr()->eq('p.store', ':store'));
        $qb->setParameter('store', $store);
        $qb->andWhere(':category MEMBER OF p.categories');
        $qb->setParameter('category', $category);
        $qb->andWhere($qb->expr()->neq('p', ':post'));
        $qb->setParameter('post', $post);
        $qb->setMaxResults($limit);
        $qb->andWhere($qb->expr()->eq('p.enabled', ':enabled'));
        $qb->setParameter('enabled', true);
        $qb->orderBy('p.publishedAt', 'DESC');
        return $qb->getQuery()->getResult();

    }

//    /**
//     * @return BlogPost[] Returns an array of BlogPost objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BlogPost
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
