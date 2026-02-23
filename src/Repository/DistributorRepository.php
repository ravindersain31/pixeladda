<?php

namespace App\Repository;

use App\Entity\Distributor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class DistributorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Distributor::class);
    }

    // Get soft-deleted distributors
    public function findDeletedQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->where('d.deletedAt IS NOT NULL')
            ->orderBy('d.deletedAt', 'DESC');
    }

    // Get active (not deleted) distributors
    public function findAllActiveQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->where('d.deletedAt IS NULL')
            ->orderBy('d.createdAt', 'DESC');
    }

    // Filter active distributors by email or phone
    public function filterDistributorsQuery(?string $email = null, ?string $phoneNumber = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.deletedAt IS NULL'); // no isDeleted column

        if ($email) {
            $qb->andWhere('d.email LIKE :email')
               ->setParameter('email', '%' . $email . '%');
        }

        if ($phoneNumber) {
            $qb->andWhere('d.phoneNumber LIKE :phone')
               ->setParameter('phone', '%' . $phoneNumber . '%');
        }

        $qb->orderBy('d.createdAt', 'DESC');

        return $qb;
    }

    // Find active distributor by ID
    public function findActiveById(int $id): ?Distributor
    {
        return $this->createQueryBuilder('d')
            ->where('d.id = :id')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Find distributor by ID (regardless of deleted)
    public function findById(int $id): ?Distributor
    {
        return $this->find($id);
    }

    // Find distributor by ID including deleted
    public function findByIdIncludingDeleted(int $id): ?Distributor
    {
        return $this->createQueryBuilder('d')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Count active distributors (not deleted and status != closed)
    public function getActiveDistributorCount(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('d.status != :closedStatus')
            ->setParameter('closedStatus', 1)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
