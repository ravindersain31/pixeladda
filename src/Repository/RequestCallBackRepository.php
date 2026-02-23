<?php

namespace App\Repository;

use App\Entity\RequestCallBack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RequestCallBack>
 *
 * @method RequestCallBack|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequestCallBack|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequestCallBack[]    findAll()
 * @method RequestCallBack[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequestCallBackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestCallBack::class);
    }

    public function save(RequestCallBack $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RequestCallBack $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findNewRequests(): array
    {
        $qb = $this->createQueryBuilder('r');

        $qb->where(
    $qb->expr()->orX(
                    $qb->expr()->eq('r.isOpened', 'true'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('r.isOpened', 'false'),
                        $qb->expr()->orX(
                        $qb->expr()->gt('r.updatedAt', ':date'),
                    )
                )
            )
        )
        ->setParameter('date', new \DateTimeImmutable('-16 days'))
        ->orderBy('r.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return RequestCallBack[] Returns an array of RequestCallBack objects
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

//    public function findOneBySomeField($value): ?RequestCallBack
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
