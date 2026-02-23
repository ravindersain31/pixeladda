<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderMessage>
 *
 * @method OrderMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderMessage[]    findAll()
 * @method OrderMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderMessage::class);
    }

    public function save(OrderMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getProofMessages(Order $order): array
    {
        $qb = $this->createQueryBuilder('OM');
        $qb->andWhere($qb->expr()->eq('OM.order', ':order'));
        $qb->andWhere($qb->expr()->in('OM.type', ':type'));
        $qb->setParameter('order', $order);
        $qb->setParameter('type', ['PROOF', 'CHANGES_REQUESTED',['PROOF_APPROVED']]);
        $qb->orderBy('OM.id', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getPrintCutFile(Order $order): array
    {
        $qb = $this->createQueryBuilder('OM');
        $qb->andWhere($qb->expr()->eq('OM.order', ':order'));
        $qb->andWhere($qb->expr()->in('OM.type', ':type'));
        $qb->setParameter('order', $order);
        $qb->setParameter('type', ['CUT_FILE', 'PRINT_FILE',['PRINT_CUT_FILE']]);
        $qb->orderBy('OM.id', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getLastProofMessage(Order $order): ?OrderMessage
    {
        $qb = $this->createQueryBuilder('OM');
        $qb->andWhere($qb->expr()->eq('OM.order', ':order'));
        $qb->andWhere($qb->expr()->in('OM.type', ':type'));
        $qb->setParameter('order', $order);
        $qb->setParameter('type', ['PROOF']);
        $qb->orderBy('OM.id', 'DESC');
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

//    /**
//     * @return OrderMessage[] Returns an array of OrderMessage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OrderMessage
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
