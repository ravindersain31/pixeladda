<?php

namespace App\Repository;

use App\Entity\Subscriber;
use App\Service\PhoneNormalizer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscriber>
 *
 * @method Subscriber|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscriber|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscriber[]    findAll()
 * @method Subscriber[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PhoneNormalizer $phoneNormalizer)
    {
        parent::__construct($registry, Subscriber::class);
    }

    public function save(Subscriber $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Subscriber $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function filterSubscribers(
        ?string $email = null,
        ?string $name = null,
        ?string $phone = null
    ): Query|array {
        $qb = $this->createQueryBuilder('u');
        $qb->groupBy('u.id');
        if ($email) {
            $qb->andWhere($qb->expr()->like('u.email', ':email'));
            $qb->setParameter('email', '%' . $email . '%');
        }

        if ($phone) {
            $phoneDigits = $this->phoneNormalizer->normalize($phone);
            $normalizedPhone = $this->phoneNormalizer->getNormalizedSql('u.phone');

            $qb->andWhere("$normalizedPhone LIKE :phone")
                ->setParameter('phone', '%' . $phoneDigits . '%');
        }

        if ($name) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.name', ':name'),
                    $qb->expr()->like('u.email', ':name')
                )
            )
            ->setParameter('name', '%' . $name . '%');
        }

        $qb->orderBy('u.id', 'DESC');

        return $qb->getQuery();
    }

    public function streamByCreatedDateRange(\DateTimeInterface $from, \DateTimeInterface $to, int $batchSize = 100): \Generator
    {
        $offset = 0;
        do {
            $results = $this->createQueryBuilder('s')
                ->where('s.createdAt BETWEEN :from AND :to')
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult();
            foreach ($results as $subscriber) {
                yield $subscriber;
            }
            $offset += $batchSize;
        } while (count($results) === $batchSize);
    }

    //    /**
    //     * @return Subscriber[] Returns an array of Subscriber objects
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

    //    public function findOneBySomeField($value): ?Subscriber
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
