<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderTransaction;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderTransaction>
 *
 * @method OrderTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderTransaction[]    findAll()
 * @method OrderTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderTransaction::class);
    }

    public function save(OrderTransaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderTransaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function filterTransaction(?string $transactionId = null, ?string $status = null, ?string $paymentMethod = null): array
    {
        $qb = $this->createQueryBuilder('t')->select('t');

        if ($transactionId) {
            $qb->where($qb->expr()->eq('t.transactionId', ':transactionId'))
                ->setParameter('transactionId', $transactionId);
        }

        if ($status) {
            $qb->andWhere($qb->expr()->eq('t.status', ':status'))
                ->setParameter('status', $status);
        }

        if ($paymentMethod) {
            $qb->andWhere($qb->expr()->eq('t.paymentMethod', ':paymentMethod'))
                ->setParameter('paymentMethod', $paymentMethod);
        }
            $qb->orderBy('t.id', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function filterSales(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
        array|string|null $status = null,
        array|string|null $paymentMethod = null,
        array|string|null $orderStatus = null,
        array|string|null $paymentStatus = null,
        bool $onlyOrderIds = false,
        bool $onlyCount = false,
        bool $onlyAmount = false,
        bool $onlyrefundedAmount = false,
        bool $calculateAverage = false,
        bool $receivedAmount = false,
        bool $result = false
    ): Query|array{
        $qb = $this->createQueryBuilder('t')->select('t');
        $qb->leftJoin('t.order', 'O');

        if ($status) {
            $qb->andWhere($qb->expr()->in('t.status', ':status'));
            $qb->setParameter('status', $status);
        }

        if ($orderStatus) {
            $qb->andWhere($qb->expr()->in('O.status', ':orderStatus'));
            $qb->setParameter('orderStatus', $orderStatus);
        }

        if ($paymentStatus) {
            $qb->andWhere($qb->expr()->in('O.paymentStatus', ':paymentStatus'));
            $qb->setParameter('paymentStatus', $paymentStatus);
        }

        if ($onlyOrderIds) {
            $qb->select('GROUP_CONCAT(DISTINCT O.orderId) as orderIds');
        }

        $qb->andWhere($qb->expr()->notIn('t.status', ':transactionStatus'));
        $qb->setParameter('transactionStatus', [PaymentStatusEnum::INITIATED, PaymentStatusEnum::PROCESSING, PaymentStatusEnum::FAILED, PaymentStatusEnum::UNKNOWN, PaymentStatusEnum::REDIRECTED_TO_GATEWAY]);

        if ($onlyCount) {
            $qb->select('COUNT(O.id)');
        }

        if ($onlyAmount) {
            $qb->select('SUM(t.amount) - SUM(t.refundedAmount) as totalAmount');
        }

        if ($onlyrefundedAmount){
            $qb->select('SUM(t.refundedAmount) as refundedAmount');
        }

        if ($calculateAverage) {
            $qb->select('AVG(t.amount) as averageAmount');
        }

        if($receivedAmount){
            $qb->select('SUM(t.amount) - SUM(t.refundedAmount) as totalAmount');
        }

        $qb->andWhere($qb->expr()->notIn('O.paymentStatus', ':failedOrders'));
        $qb->setParameter('failedOrders', [PaymentStatusEnum::INITIATED, PaymentStatusEnum::PROCESSING, PaymentStatusEnum::FAILED, PaymentStatusEnum::UNKNOWN]);

        $qb->andWhere($qb->expr()->neq('O.status', ':cancelled'));
        $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);

        if ($paymentMethod) {
            if (is_array($paymentMethod)) {
                $qb->andWhere($qb->expr()->in('t.paymentMethod', ':paymentMethod'));
            } else {
                $qb->andWhere($qb->expr()->eq('t.paymentMethod', ':paymentMethod'));
            }
            $qb->setParameter('paymentMethod', $paymentMethod);
        }

        if ($fromDate) {
            $qb->andWhere($qb->expr()->gte('O.orderAt', ':fromDate'));
            $qb->setParameter('fromDate', $fromDate);
        }

        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
            $qb->setParameter('endDate', $endDate);
        }

        $qb->orderBy('t.id', 'ASC');

        $query = $qb->getQuery();

        if($result){
            return $query->getResult();
        }

        return $query;
    }

    public function getAverageOrderPrice(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
    ){

        $amount =  self::filterSales(fromDate: $fromDate, endDate: $endDate, receivedAmount: true, result: true);

        $count = $this->getEntityManager()->getRepository(Order::class)->filterOrder(
                            fromDate: $fromDate,
                            endDate: $endDate,
                            onlyCount: true,
                            result: true
                        );
        if (isset($amount[0]['totalAmount']) && isset($count[0]['totalOrders']) && ($amount[0]['totalAmount'] > 0 && $count[0]['totalOrders'] > 0)) {
            return number_format($amount[0]['totalAmount'] / $count[0]['totalOrders'], 2, '.', '');
        }else{
            return 0;
        }
    }

//    /**
//     * @return OrderTransaction[] Returns an array of OrderTransaction objects
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

//    public function findOneBySomeField($value): ?OrderTransaction
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
