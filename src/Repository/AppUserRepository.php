<?php

namespace App\Repository;

use App\Entity\AppUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppUser>
 *
 * @method AppUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppUser[]    findAll()
 * @method AppUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppUser::class);
    }

    public function save(AppUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AppUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUsersWithOrders(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): Query|array {
        $todayDate = [
            'start' => (new \DateTimeImmutable())->setTime(0, 0, 0),
            'end' => (new \DateTimeImmutable())->setTime(23, 59, 59),
        ];

        $qb = $this->createQueryBuilder('u');
        $qb->leftJoin('u.orders', 'O');
        $qb->groupBy('u.id');

        $dateFilterAdded = false;

        if ($fromDate) {
            $qb->andWhere($qb->expr()->gte('O.orderAt', ':fromDate'));
            $qb->setParameter('fromDate', $fromDate->setTime(0, 0, 0));
            $dateFilterAdded = true;
        }

        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
            $qb->setParameter('endDate', $endDate->setTime(23, 59, 59));
            $dateFilterAdded = true;
        }

        if (!$dateFilterAdded) {
            $qb->andWhere($qb->expr()->between('O.orderAt', ':todayStart', ':todayEnd'));
            $qb->setParameter('todayStart', $todayDate['start']);
            $qb->setParameter('todayEnd', $todayDate['end']);
        }

        $qb->orderBy('u.id', 'DESC');
        return $qb->getQuery();
    }


    public function filterUsers(?string $email = null, ?string $name = null): Query
    {
        $qb = $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode('ROLE_USER'))
            ->orderBy('u.id', 'DESC');

        if ($email) {
            $orX = $qb->expr()->orX(
                $qb->expr()->like('u.username', ':email'),
                $qb->expr()->like('u.email', ':email')
            );
            $qb->andWhere($orX)
                ->setParameter('email', '%' . $email . '%');
        }

        if ($name) {
            $orX = $qb->expr()->orX(
                $qb->expr()->like('u.name', ':name'),
                $qb->expr()->like('u.username', ':name'),
                $qb->expr()->like('u.email', ':name')
            );
            $qb->andWhere($orX)
                ->setParameter('name', '%' . $name . '%');
        }

        return $qb->getQuery();
    }


    public function findWholeSellersQuery(
        ?string $email = null,
        ?string $name = null,
        $status = null 
    ): Query {
        $qb = $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode('ROLE_WHOLE_SELLER'))
            ->orderBy('u.id', 'DESC');

        if ($status !== null) {
            $qb->andWhere('u.wholeSellerStatus = :status')
            ->setParameter('status', $status);
        }

        if ($email) {
            $orX = $qb->expr()->orX(
                $qb->expr()->like('u.username', ':email'),
                $qb->expr()->like('u.email', ':email')
            );
            $qb->andWhere($orX)
                ->setParameter('email', '%' . $email . '%');
        }

        if ($name) {
            $orX = $qb->expr()->orX(
                $qb->expr()->like('u.name', ':name'),
                $qb->expr()->like('u.username', ':name'),
                $qb->expr()->like('u.email', ':name')
            );
            $qb->andWhere($orX)
                ->setParameter('name', '%' . $name . '%');
        }

        return $qb->getQuery();
    }


    //    /**
//     * @return AppUser[] Returns an array of AppUser objects
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

//    public function findOneBySomeField($value): ?AppUser
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
