<?php

namespace App\Repository;

use App\Entity\Fraud;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Enum\DBInstanceEnum;
use App\Trait\EntityManagerInstanceTrait;

/**
 * @extends ServiceEntityRepository<Fraud>
 *
 * @method Fraud|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fraud|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fraud[]    findAll()
 * @method Fraud[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FraudRepository extends ServiceEntityRepository
{

    use EntityManagerInstanceTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fraud::class);
        $this->setManagerRegistry($registry);
    }

    public function save(Fraud $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Fraud $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function fetchAll()
    {
        return $this->createQueryBuilder('f')->getQuery()->getResult();
    }

    public function isFraudOrder($orders, ?Order $order = null): array|null
    {
        $fraudResults = [];

        $ordersToCheck = $order ? [$order] : $orders;
        foreach ($ordersToCheck as $order) {
            $email = $order?->getUser()?->getEmail() ?? null;
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            if(!$email) {
                continue;
            }
            $this->checkAddressForFraud($order, $fraudResults);
        }
        return !empty($fraudResults) ? $fraudResults : null;
    }

    private function checkAddressForFraud(Order $order, &$fraudResults): void
    {
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $email = $order->getUser()->getEmail();

        $qb = $this->createQueryBuilder('F', dbInstance: DBInstanceEnum::READER);

        if (!empty($email)) {
            $qb->orWhere($qb->expr()->like('F.email', ':email'))
               ->setParameter('email', '%' . $email . '%');
        }
        else {
            if (!empty($shippingAddress['phone'])) {
                $qb->orWhere($qb->expr()->like('F.phoneNumber', ':shippingPhone'))
                   ->setParameter('shippingPhone', '%' . $shippingAddress['phone'] . '%');
            }

            if (!empty($billingAddress['phone'])) {
                $qb->orWhere($qb->expr()->like('F.phoneNumber', ':billingPhone'))
                   ->setParameter('billingPhone', '%' . $billingAddress['phone'] . '%');
            }

            if (!empty($shippingAddress['addressLine1'])) {
                $qb->orWhere($qb->expr()->like('F.addressLine1', ':shippingAddressLine1'))
                   ->setParameter('shippingAddressLine1', '%' . $shippingAddress['addressLine1'] . '%');
            }

            if (!empty($billingAddress['addressLine1'])) {
                $qb->orWhere($qb->expr()->like('F.addressLine1', ':billingAddressLine1'))
                   ->setParameter('billingAddressLine1', '%' . $billingAddress['addressLine1'] . '%');
            }

            if (!empty($shippingAddress['firstName'])) {
                $qb->orWhere($qb->expr()->like('F.firstName', ':shippingFirstName'))
                   ->setParameter('shippingFirstName', '%' . $shippingAddress['firstName'] . '%');
            }

            if (!empty($billingAddress['firstName'])) {
                $qb->orWhere($qb->expr()->like('F.firstName', ':billingFirstName'))
                   ->setParameter('billingFirstName', '%' . $billingAddress['firstName'] . '%');
            }

            if (!empty($shippingAddress['lastName'])) {
                $qb->orWhere($qb->expr()->like('F.lastName', ':shippingLastName'))
                   ->setParameter('shippingLastName', '%' . $shippingAddress['lastName'] . '%');
            }

            if (!empty($billingAddress['lastName'])) {
                $qb->orWhere($qb->expr()->like('F.lastName', ':billingLastName'))
                   ->setParameter('billingLastName', '%' . $billingAddress['lastName'] . '%');
            }
        }

        $qb->orderBy('F.id', 'DESC');

        $result = $qb->getQuery()->getResult();

        if (!empty($result)) {
            $fraudResults[] = $order->getOrderId();
        }
    }

//    /**
//     * @return Fraud[] Returns an array of Fraud objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Fraud
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
