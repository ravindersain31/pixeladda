<?php

namespace App\Repository;

use App\Entity\BulkOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<BulkOrder>
 */
class BulkOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BulkOrder::class);
    }

    public function filterOrderDetails(?string $email = null, ?string $phoneNumber = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC');

        if ($email) {
            $qb->orWhere('b.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }

        if ($phoneNumber) {
            $qb->orWhere('b.phoneNumber LIKE :phone')
                ->setParameter('phone', '%' . $phoneNumber . '%');
        }

        return $qb;
    }
}
