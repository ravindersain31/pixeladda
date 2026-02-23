<?php

namespace App\Repository;

use App\Entity\Artwork;
use App\Entity\ArtworkCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artwork>
 *
 * @method Artwork|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artwork|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artwork[]    findAll()
 * @method Artwork[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artwork::class);
    }

    public function save(Artwork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Artwork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findClipart(?ArtworkCategory $category = null, ?string $query = null)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere($qb->expr()->eq('a.status', ':status'));
        $qb->setParameter('status', true);

        if ($category && !$query) {
            $qb->andWhere($qb->expr()->eq('a.category', ':category'));
            $qb->setParameter('category', $category);
        }

        if ($query) {
            $qb->andWhere($qb->expr()->like('a.tags', ':query'));
            $qb->setParameter('query', '%' . $query . '%');
        }
        $qb->orderBy('a.id', 'ASC');

        $qb->setMaxResults(108);
        return $qb->getQuery()->getResult();

    }

    public function getFilteredArtwork(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC');

        if (!empty($filters['search'])) {
            $qb->andWhere('a.tags LIKE :search OR a.image.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['image_name'])) {
            $qb->andWhere('a.image.name LIKE :imageName')
               ->setParameter('imageName', '%' . $filters['image_name'] . '%');
        }

        return $qb;
    }

//    /**
//     * @return Artwork[] Returns an array of Artwork objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Artwork
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
