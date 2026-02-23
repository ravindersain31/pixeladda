<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\SavedCart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SavedCart>
 *
 * @method SavedCart|null find($id, $lockMode = null, $lockVersion = null)
 * @method SavedCart|null findOneBy(array $criteria, array $orderBy = null)
 * @method SavedCart[]    findAll()
 * @method SavedCart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SavedCartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedCart::class);
    }

    public function save(SavedCart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SavedCart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function deleteExpired(int $expireAfterDays = 30): Query
    {
        $qb = $this->createQueryBuilder('SC');
        $qb->delete();
        $qb->where($qb->expr()->lt('SC.createdAt', ':expireAfter'));
        $qb->setParameter('expireAfter', (new \DateTime())->modify("-$expireAfterDays days"));

        return $qb->getQuery();
    }


    public function findPromoSavedCartCustomer($user): array
    {
        return $this->createQueryBuilder('sd')
            ->innerJoin('sd.storeDomain', 'sdm')
            ->andWhere('sd.user = :user')
            ->andWhere('sdm.name = :promo')
            ->setParameter('user', $user)
            ->setParameter('promo', 'Promo')
            ->orderBy('sd.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSavedCartCustomer($user): array
    {
        $qb = $this->createQueryBuilder('sd');

        $qb->leftJoin('sd.storeDomain', 'sdm')
        ->addSelect('sdm')
        ->andWhere('sd.user = :user')
        ->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('sdm.name'),
                $qb->expr()->neq('sdm.name', ':promo')
            )
        )
        ->setParameter('user', $user)
        ->setParameter('promo', 'Promo')
        ->orderBy('sd.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    //    /**
//     * @return SavedCart[] Returns an array of SavedCart objects
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

//    public function findOneBySomeField($value): ?SavedCart
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
