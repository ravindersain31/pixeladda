<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\ShippingEnum;
use App\Enum\ShippingStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    private const ALLOWED_SORT_FIELDS = [
        'orderAt'        => 'O.orderAt',
        'orderId'        => 'O.id',
        'totalAmount'    => 'O.totalAmount',
        'status'         => 'O.status',
        'paymentStatus'  => 'O.paymentStatus',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function fetchAll(bool $result = false): Query|array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->orderBy('O.id', 'DESC');
        $query = $qb->getQuery();
        if ($result) {
            return $query->getResult();
        }
        return $query;
    }

    public function findByStatus(string $status, bool $result = false): Query|array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->andWhere($qb->expr()->eq('O.status', ':status'));
        $qb->setParameter('status', $status);
        $qb->orderBy('O.id', 'DESC');
        $query = $qb->getQuery();
        if ($result) {
            return $query->getResult();
        }
        return $query;
    }

    public function findByOrderId(string $orderId): ?Order
    {
        $qb = $this->createQueryBuilder('O');

        $qb->andWhere($qb->expr()->eq('O.orderId', ':orderId'));
        $qb->setParameter('orderId', $orderId);

        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findRelatedOrderByCustomer(User $user, ?string $shippingEmail, ?string $billingEmail, bool $result = true): Query|array
    {
        $qb = $this->createQueryBuilder('O');
        $expr = $qb->expr();

        $qb->select('DISTINCT O'); 
        $qb->leftJoin('O.user', 'u');

        $orX = $expr->orX(
            $expr->eq('O.user', ':user'),
            $expr->eq("JSON_UNQUOTE(JSON_EXTRACT(O.shippingAddress, '$.email'))", ':shippingEmail'),
            $expr->eq("JSON_UNQUOTE(JSON_EXTRACT(O.shippingAddress, '$.email'))", ':billingEmail'),
            $expr->eq("JSON_UNQUOTE(JSON_EXTRACT(O.billingAddress, '$.email'))", ':shippingEmail'),
            $expr->eq("JSON_UNQUOTE(JSON_EXTRACT(O.billingAddress, '$.email'))", ':billingEmail')
        );

        $qb->andWhere($orX);

        $qb->andWhere($expr->in('O.orderChannel', ':orderChannel'));
        $qb->setParameter('user', $user);
        $qb->setParameter('shippingEmail', $shippingEmail);
        $qb->setParameter('billingEmail', $billingEmail);
        $qb->setParameter('orderChannel', [
            OrderChannelEnum::CHECKOUT,
            OrderChannelEnum::EXPRESS,
            OrderChannelEnum::REPLACEMENT,
            OrderChannelEnum::SM3,
            OrderChannelEnum::SALES,
            OrderChannelEnum::SALE,
        ]);
        $qb->orderBy('O.id', 'DESC');
        if ($result) {
            return $qb->getQuery()->getResult();
        }
        return $qb->getQuery();
    }

    public function findOrderByCustomer(User $user, ?string $sort = null, string $dir = 'DESC', bool $result = true): Query|array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->leftJoin('O.user', 'u');
        $qb->andWhere($qb->expr()->eq('O.user', ':user'));
        $qb->setParameter('user', $user);
        $qb->leftJoin('O.storeDomain', 'sd')->addSelect('sd');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('sd.name'),
            $qb->expr()->neq('sd.name', ':promo')
        ))
        ->setParameter('promo', 'Promo');
        $qb->andWhere($qb->expr()->neq('O.status', ':created'));
        $qb->setParameter('created', OrderStatusEnum::CREATED);

        $qb->andWhere($qb->expr()->notIn('O.paymentStatus', ':paymentStatus'));
        $qb->setParameter('paymentStatus', [PaymentStatusEnum::INITIATED, PaymentStatusEnum::PROCESSING, PaymentStatusEnum::FAILED]);
        
        $qb->andWhere($qb->expr()->isNUll('O.parent'));
        
        if ($sort && isset(self::ALLOWED_SORT_FIELDS[$sort])) {
            $qb->orderBy(self::ALLOWED_SORT_FIELDS[$sort], strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('O.id', 'DESC');
        }

        if ($result) {
            return $qb->getQuery()->getResult();
        }
        return $qb->getQuery();
    }

    public function findPromoOrdersByCustomer(User $user, ?string $sort = null, string $dir = 'DESC', bool $result = true): Query|array
    {
        $qb = $this->createQueryBuilder('O');

        $qb->leftJoin('O.user', 'u')
        ->addSelect('u');

        $qb->leftJoin('O.storeDomain', 'sd')
        ->addSelect('sd');

        $qb->andWhere('O.user = :user')
        ->setParameter('user', $user);

        $qb->andWhere('sd.name = :promo')
        ->setParameter('promo', 'Promo');

        $qb->andWhere('O.status != :created')
        ->setParameter('created', OrderStatusEnum::CREATED);

        $qb->andWhere($qb->expr()->notIn('O.paymentStatus', ':paymentStatus'))
        ->setParameter('paymentStatus', [
            PaymentStatusEnum::INITIATED,
            PaymentStatusEnum::PROCESSING,
            PaymentStatusEnum::FAILED
        ]);

        $qb->andWhere('O.parent IS NULL');

        if ($sort && isset(self::ALLOWED_SORT_FIELDS[$sort])) {
            $qb->orderBy(self::ALLOWED_SORT_FIELDS[$sort], strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $qb->orderBy('O.id', 'DESC');
        }

        return $result
            ? $qb->getQuery()->getResult()
            : $qb->getQuery();
    }

    public function findOrderByEmailTelephone(string $email, string $telephone, bool $isPromoStore): array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->leftJoin('O.user', 'u')
        ->leftJoin('O.storeDomain', 'sd')
        ->addSelect('sd');
        $qb->andWhere($qb->expr()->like('O.shippingAddress', ':email'))
        ->andWhere($qb->expr()->like('O.shippingAddress', ':telephone'))
        ->setParameter('email', '%' . $email . '%')
        ->setParameter('telephone', '%' . $telephone . '%')
        ->andWhere($qb->expr()->neq('O.status', ':created'))
        ->setParameter('created', OrderStatusEnum::CREATED)
        ->orderBy('O.id', 'DESC');

        if ($isPromoStore) {
            $qb->andWhere('sd.name = :promo')
            ->setParameter('promo', 'Promo');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('sd.name'),
                    $qb->expr()->neq('sd.name', ':promo')
                )
            )->setParameter('promo', 'Promo');
        }
        return $qb->getQuery()->getResult();
    }

    public function findOrderProofByDetails($email, $telephone, $orderId, bool $isPromoStore): array
    {
        $qb = $this->createQueryBuilder('O')
            ->select(
                'O.orderId',
                'O.totalAmount',
                "REPLACE(O.status, '_', ' ') AS status",
                'O.orderAt',
                'O.proofApprovedAt',
                'O.paymentStatus',
                "CONCAT(
                '{\"categories\": [',
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        '{\"name\": \"', PC.name, '\", \"slug\": \"', PC.slug, '\"}'
                    ) ORDER BY PC.name SEPARATOR ', '
                ),
                ']}'
            ) AS categories"
            )
            ->leftJoin('O.user', 'u')
            ->leftJoin('O.orderItems', 'OI')
            ->leftJoin('OI.product', 'P')
            ->leftJoin('P.parent', 'PP')
            ->leftJoin('PP.primaryCategory', 'PC')
             ->leftJoin('O.storeDomain', 'sd')
            ->groupBy('O.id');

        if (!empty($orderId)) {
            $qb->andWhere($qb->expr()->eq('O.orderId', ':orderId'));
            $qb->setParameter('orderId', $orderId);
        }

        if (!empty($email)) {
            $qb->andWhere($qb->expr()->like('u.username', ':email'))
                ->andWhere($qb->expr()->like('O.shippingAddress', ':email'))
                ->andWhere($qb->expr()->like('O.billingAddress', ':email'))
                ->setParameter('email', '%' . $email . '%');
        }

        if (!empty($telephone)) {
            $qb->andWhere($qb->expr()->like('O.shippingAddress', ':telephone'))
                ->andWhere($qb->expr()->like('O.billingAddress', ':telephone'))
                ->setParameter('telephone', '%' . $telephone . '%');
        }

        if ($isPromoStore) {
            $qb->andWhere('sd.name = :promo');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('sd.name'),
                    $qb->expr()->neq('sd.name', ':promo')
                )
            );
        }

        $qb->setParameter('promo', 'Promo')
        ->andWhere('O.status != :created')
        ->setParameter('created', OrderStatusEnum::CREATED)
        ->orderBy('O.id', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function findOrderCustomPages(string $status, bool $canceledOrders = false): Query|array
    {
        $todayDate = [
            'start' => (new \DateTimeImmutable())->setTime(0, 0, 0),
            'end' => (new \DateTimeImmutable())->setTime(23, 59, 59),
        ];
        $qb = $this->createQueryBuilder('O');
        if (OrderStatusEnum::CREATED !== $status) {
            $qb->andWhere($qb->expr()->neq('O.status', ':created'));
            $qb->setParameter('created', OrderStatusEnum::CREATED);
        }

        // excluding all cancelled orders
        if (!$canceledOrders) {
            $qb->andWhere($qb->expr()->neq('O.status', ':cancelled'));
            $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);
        }

        if ($status === 'check-po') {
            $qb->andWhere($qb->expr()->eq('O.paymentMethod', ':paymentMethod'));
            $qb->setParameter('paymentMethod', PaymentMethodEnum::CHECK);
            $qb->orderBy('O.id', 'DESC');
        } else if ($status === 'order-protection-orders') {
            $qb->andWhere($qb->expr()->between('O.orderAt', ':startDate', ':endDate'));
            $qb->andWhere($qb->expr()->neq('O.orderProtectionAmount', 0));
            $qb->orderBy('O.id', 'DESC');
            $qb->setParameter('startDate', $todayDate['start']);
            $qb->setParameter('endDate', $todayDate['end']);
        } else if ($status === 'upload-proof') {
            $qb->andWhere($qb->expr()->in('O.status', ':received'));
            $qb->setParameter('received', [OrderStatusEnum::RECEIVED, OrderStatusEnum::CHANGES_REQUESTED]);
            $qb->orderBy('O.id', 'DESC');
        } else if ($status === 'today-super-rush-order') {
            $qb->andWhere($qb->expr()->between('O.orderAt', ':startDate', ':endDate'));
            $qb->andWhere($qb->expr()->eq('O.isSuperRush', ':isSuperRush'));
            $qb->setParameter('startDate', $todayDate['start']);
            $qb->setParameter('endDate', $todayDate['end']);
            $qb->setParameter('isSuperRush', true);
        } else if ($status === 'refunded') {
            $qb->andWhere($qb->expr()->in('O.paymentStatus', ':paymentStatus'));
            $qb->setParameter('paymentStatus', [OrderStatusEnum::REFUNDED, OrderStatusEnum::PARTIALLY_REFUNDED]);
        } else {
            $qb->andWhere($qb->expr()->eq('O.status', ':received'));
            $qb->setParameter('received', OrderStatusEnum::RECEIVED);
            $qb->orderBy('O.id', 'DESC');
        }

        return $qb->getQuery();
    }

    public function findOrdersGroupedByUser(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
        bool                $onlyCount = false,
        bool                $canceledOrders = false
    )
    {
        $qb = $this->createQueryBuilder('O');

        $qb->select('u.email as email', 'O.orderId as orderId', 'u.id as id')
            ->leftJoin('O.user', 'u')
            ->groupBy('u.email', 'O.orderId', 'u.id');

        $qb->andWhere($qb->expr()->notIn('O.paymentStatus', ':paymentStatus'));
        $qb->setParameter('paymentStatus', [PaymentStatusEnum::INITIATED, PaymentStatusEnum::PROCESSING, PaymentStatusEnum::FAILED]);

        if ($fromDate !== null) {
            $qb->andWhere('O.orderAt >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('O.orderAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        if (!$canceledOrders) {
            $qb->andWhere($qb->expr()->neq('O.status', ':cancelled'));
            $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);
        }

        $query = $qb->getQuery();
        $results = $query->getResult();

        $groupedData = [];
        foreach ($results as $result) {
            $email = $result['email'];
            $orderId = $result['orderId'];
            $id = $result['id'];

            if (!isset($groupedData[$email])) {
                $groupedData[$email] = ['email' => $email, 'id' => $id, 'orderIds' => []];
            }

            $groupedData[$email]['orderIds'][] = $orderId;
        }

        $filteredData = array_filter($groupedData, function ($userData) {
            return count($userData['orderIds']) >= 2;
        });

        if ($onlyCount) {
            return count($filteredData);
        }

        return $filteredData;
    }

    public function filterOrder(
        ?array              $status = [],
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
        ?bool               $isOrderProtection = false,
        string|array|null   $paymentStatus = null,
        ?string             $paymentMethod = null,
        ?string             $search = null,
        bool                $onlyCount = false,
        bool                $onlyOrderIds = false,
        bool                $onlyAmount = false,
        bool                $onlyReceivedAmount = false,
        bool                $onlyRefundedAmount = false,
        bool                $result = false,
        bool                $isSuperRush = false,
        bool                $canceledOrders = false,
        bool                $calculateAverage = false,
        bool                $paymentLinkAmount = false,
        bool                $unMadeSigns = false,
        ?string              $printerName = null,
        string              $orderByField = 'id',
        string              $orderBy = 'ASC',
        ?string             $hasShipping = null,
    ): Query|array
    {
        $qb = $this->createQueryBuilder('O');

        if (!in_array(OrderStatusEnum::CREATED, $status)) {
            $qb->andWhere($qb->expr()->neq('O.status', ':created'));
            $qb->setParameter('created', OrderStatusEnum::CREATED);
        }
        if ($onlyCount) {
            $qb->select('COUNT(DISTINCT O.id) as totalOrders');
        }
        if ($onlyOrderIds) {
            $qb->select('GROUP_CONCAT(DISTINCT O.orderId) as orderIds');
        }

        if ($calculateAverage) {
            $qb->select('AVG(O.totalAmount) as averageAmount');
        }

        if ($onlyAmount) {
            if (
                $paymentStatus && (is_array($paymentStatus) && !in_array(PaymentStatusEnum::COMPLETED, $paymentStatus)) && (
                    (((in_array(PaymentStatusEnum::REFUNDED, $paymentStatus) || in_array(PaymentStatusEnum::PARTIALLY_REFUNDED, $paymentStatus))))
                    || ((in_array($paymentStatus, [PaymentStatusEnum::REFUNDED, PaymentStatusEnum::PARTIALLY_REFUNDED])))
                )
            ) {
                $qb->select('SUM(O.refundedAmount) as refundedAmount');
            } else if ($isOrderProtection) {
                $qb->select('SUM(O.orderProtectionAmount) as totalAmount');
            } else if ($onlyReceivedAmount) {
                $qb->select('SUM(O.totalReceivedAmount) as totalAmount');
            } else if ($onlyRefundedAmount) {
                $qb->select('SUM(O.refundedAmount) as totalAmount');
            } else {
                if ($paymentLinkAmount) {
                    $qb->select('SUM(O.paymentLinkAmountReceived) as totalAmount');
                } else {
                    $qb->select('SUM(O.totalAmount) as totalAmount');
                }
            }
        }

        if (array_filter($status)) {
            $qb->andWhere($qb->expr()->in('O.status', ':status'));
            $qb->setParameter('status', $status);
        }

        $qb->andWhere($qb->expr()->notIn('O.paymentStatus', ':failedOrders'));
        $qb->setParameter('failedOrders', [PaymentStatusEnum::INITIATED, PaymentStatusEnum::PROCESSING, PaymentStatusEnum::FAILED]);

        // excluding all cancelled orders
        if (!$canceledOrders) {
            $qb->andWhere($qb->expr()->neq('O.status', ':cancelled'));
            $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);
        }

        if ($fromDate) {
            $qb->andWhere($qb->expr()->gte('O.orderAt', ':fromDate'));
            $qb->setParameter('fromDate', $fromDate);
        }

        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
            $qb->setParameter('endDate', $endDate);
        }

        if ($paymentStatus) {
            if (is_array($paymentStatus)) {
                $qb->andWhere($qb->expr()->in('O.paymentStatus', ':paymentStatus'));
            } else {
                $qb->andWhere($qb->expr()->eq('O.paymentStatus', ':paymentStatus'));
            }
            $qb->setParameter('paymentStatus', $paymentStatus);
        }

        if ($paymentMethod) {
            $qb->andWhere($qb->expr()->eq('O.paymentMethod', ':paymentMethod'));
            $qb->setParameter('paymentMethod', $paymentMethod);
        }

        if ($isOrderProtection) {
            $qb->andWhere($qb->expr()->neq('O.orderProtectionAmount', 0));
        }

        if ($isSuperRush) {
            $qb->andWhere($qb->expr()->eq('O.isSuperRush', $isSuperRush));
        }

        if ($search) {
            $qb->leftJoin('O.orderShipments', 'OS');

            $searchArray = array_merge(preg_split('/[\s]+/', $search), [$search]);
            $orX = $qb->expr()->orX();
            foreach ($searchArray as $index => $query) {
                $normalizedQuery = preg_replace('/\D/', '', $query);

                if (preg_match('/^[a-zA-Z0-9\(\)\-]+$/', $query) || preg_match('/[a-zA-Z0-9\(\)\-]/', $query)) {
                    $orX->add(
                        $qb->expr()->orX(
                            $qb->expr()->like('LOWER(O.orderId)', "LOWER(:query_" . $index . ")"),
                            $qb->expr()->like('LOWER(O.shippingTrackingId)', "LOWER(:query_" . $index . ")"),
                            $qb->expr()->like('LOWER(O.billingAddress)', "LOWER(:query_" . $index . ")"),
                            $qb->expr()->like('LOWER(O.shippingAddress)', "LOWER(:query_" . $index . ")"),
                            $qb->expr()->like('LOWER(OS.trackingId)', "LOWER(:query_" . $index . ")")
                        )
                    );
                    $qb->setParameter('query_' . $index, "%" . $query . "%");
                }
                if (preg_match('/^\d{10}$/', $normalizedQuery)) {
                    $formattedPhone = sprintf("(%s)-%s-%s",
                        substr($normalizedQuery, 0, 3),
                        substr($normalizedQuery, 3, 3),
                        substr($normalizedQuery, 6, 4)
                    );
                    $orX->add(
                        $qb->expr()->orX(
                            $qb->expr()->like('O.billingAddress', ":phoneQuery_" . $index),
                            $qb->expr()->like('O.shippingAddress', ":phoneQuery_" . $index)
                        )
                    );
                    $qb->setParameter('phoneQuery_' . $index, "%" . $formattedPhone . "%");
                }
            }

            $qb->andWhere($orX);
        }

        if ($printerName) {
            $qb->andWhere($qb->expr()->eq('O.printerName', ':printerName'));
            $qb->setParameter('printerName', $printerName);
        }

        if ($hasShipping) {
            $qb->leftJoin('O.warehouseOrder', 'OW');
            $qb->andWhere($qb->expr()->in('OW.printStatus', [WarehouseOrderStatusEnum::DONE]));
            if ($hasShipping === 'yes') {
                $qb->andWhere($qb->expr()->eq('O.shippingMethod', ':shippingMethod'));
                $qb->setParameter('shippingMethod', ShippingEnum::EASYPOST);
                $qb->andWhere($qb->expr()->eq('O.shippingStatus', ':shippingStatus'));
                $qb->setParameter('shippingStatus', ShippingStatusEnum::LABEL_PURCHASED);
                $qb->andWhere($qb->expr()->isNotNull('O.shippingOrderId'));
            } else {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->isNull('O.shippingOrderId'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('O.shippingMethod', ':shippingMethod'),
                        $qb->expr()->notIn('O.shippingStatus', ':shippingStatus'),
                    )
                ));
                $qb->setParameter('shippingMethod', ShippingEnum::EASYPOST);
                $qb->setParameter('shippingStatus', [ShippingStatusEnum::SHIPPED, ShippingStatusEnum::LABEL_PURCHASED]);
            }
        }

        $qb->orderBy('O.' . $orderByField, $orderBy);

        $query = $qb->getQuery();

        if ($result) {
            return $query->getResult();
        }


        return $query;
    }

    public function getByDate(\DateTimeImmutable $date)
    {

        $qb = $this->createQueryBuilder('O');
        $qb->leftJoin('O.orderItems', 'OI');
        $qb->leftJoin('O.transactions', 'T');

        $qb->select('O.id, O.orderId,  COUNT(OI) as totalUnit, O.status, O.paymentStatus, O.paymentMethod, O.totalReceivedAmount, O.refundedAmount, O.paymentLinkAmountReceived, O.totalAmount as totalOrderAmount');
        // $qb->addSelect('SUM(CASE WHEN T.status IN (:receivedTransactionStatus) THEN T.amount ELSE 0 END) as totalReceivedAmount');
        // $qb->addSelect('SUM(CASE WHEN T.status IN (:receivedTransactionStatus) THEN T.refundedAmount ELSE 0 END) as totalRefundedAmount');
        // $qb->setParameter('receivedTransactionStatus', [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::REFUNDED, PaymentStatusEnum::PARTIALLY_REFUNDED]);

        $qb->andWhere($qb->expr()->notIn('O.status', ':status'));
        $qb->setParameter('status', [OrderStatusEnum::CREATED, OrderStatusEnum::REFUNDED, OrderStatusEnum::CANCELLED, OrderStatusEnum::ARCHIVE]);

        $qb->andWhere($qb->expr()->gte('O.orderAt', ':startDate'));
        $startDate = (clone $date)->setTime(0, 0, 0);
        $qb->setParameter('startDate', $startDate);

        $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
        $endDate = (clone $date)->setTime(23, 59, 59);
        $qb->setParameter('endDate', $endDate);

        $qb->andWhere($qb->expr()->isNull('O.deletedAt'));
        $qb->groupBy('O.id');

        return $qb->getQuery()->getResult();
    }

    public function findRepeatOrders(
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
    )
    {
        $qb = $this->createQueryBuilder('O');

        $result = $qb
            ->select('COUNT(O.orderId)')
            ->leftJoin('O.user', 'u');

        if ($fromDate !== null) {
            $result
                ->andWhere('O.orderAt >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        if ($endDate !== null) {
            $result
                ->andWhere('O.orderAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $result->getQuery();
    }

    public function leaveAReview(\DateTimeImmutable $date, string $type = 'review', int $limit = 50, int $offset = 0)
    {

        $shippedBefore10Days = clone $date;
        $shippedBefore10Days = $shippedBefore10Days->modify('-7 days')->setTime(0, 0, 0);

        $qb = $this->createQueryBuilder('O');
        
        $qb->select('O.id');
        
        $qb->andWhere($qb->expr()->in('O.status', ':status'));
        $qb->setParameter('status', [OrderStatusEnum::SHIPPED, OrderStatusEnum::COMPLETED]);

        $qb->andWhere($qb->expr()->lte('O.shippingDate', ':shippingDate'));
        $qb->setParameter('shippingDate', $shippedBefore10Days);

        $qb->andWhere($qb->expr()->isNull('O.deletedAt'));

        if ($type === 'review') {
            $qb->andWhere($qb->expr()->isNull('O.leaveAReviewSentAt'));
        }

        if ($type === 'photo_review') {
            $qb->andWhere($qb->expr()->isNull('O.leaveAPhotoReviewSentAt'));
        }

        if ($type === 'video_review') {
            $qb->andWhere($qb->expr()->isNull('O.leaveAVideoReviewSentAt'));
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }


    public function getShippingCostForOrderBetween(?\DateTimeImmutable $fromDate = null, ?\DateTimeImmutable $endDate = null): array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->select('SUM(O.companyShippingCost) as shippingCost');

        if ($fromDate) {
            $qb->andWhere($qb->expr()->gte('O.orderAt', ':fromDate'));
            $qb->setParameter('fromDate', $fromDate);
        }

        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
            $qb->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getCanceledOrders(
        array $status =         [],
        ?\DateTimeImmutable      $fromDate = null,
        ?\DateTimeImmutable      $endDate = null
    ): array
    {
        $qb = $this->createQueryBuilder('O');

        $qb->select('SUM(O.totalAmount) as totalAmount');

        $qb->andWhere($qb->expr()->in('O.status', ':status'));
        $qb->andWhere($qb->expr()->gte('O.orderAt', ':fromDate'));
        $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
        $qb->setParameter('status', $status);
        $qb->setParameter('fromDate', $fromDate);
        $qb->setParameter('endDate', $endDate);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getTotalQuantityOfOrders(array $status = [])
    {
        $qb = $this->createQueryBuilder('O');
        $qb->leftJoin('O.orderItems', 'OI');

        $qb->select('SUM(OI.quantity) as totalQuantity');
        $qb->andWhere($qb->expr()->in('O.status', ':status'));
        $qb->andWhere($qb->expr()->notIn('O.status', [OrderStatusEnum::SHIPPED, OrderStatusEnum::COMPLETED, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::CANCELLED]));
        $qb->setParameter('status', $status);

        $query = $qb->getQuery();

        $totalUnmadeSigns = $query->getSingleScalarResult();

        return $totalUnmadeSigns;
    }

    public function findOrdersForProofReminders(
        array $status,
        $version = 'V2'
    ): Query
    {
        $qb = $this->createQueryBuilder('O');

        $qb->andWhere($qb->expr()->in('O.status', ':status'));
        $qb->andWhere($qb->expr()->eq('O.version', ':version'));
        $qb->setParameter('version', $version);
        $qb->setParameter('status', $status);

        return $qb->getQuery();
    }

    public function getFraudOrders(array $frauds, bool $getResult = false)
    {
        $qb = $this->createQueryBuilder('O')
            ->innerJoin('O.user', 'U')
            ->addSelect('U');

        $orX = $qb->expr()->orX();

        foreach ($frauds as $fraud) {
            $conditions = [];

            if (!empty($fraud->getEmail())) {
                $conditions[] = $qb->expr()->like('U.email', ':email_' . $fraud->getId());
                $qb->setParameter('email_' . $fraud->getId(), '%' . $fraud->getEmail() . '%');
            } else {
                if (!empty($fraud->getPhoneNumber())) {
                    $conditions[] = $qb->expr()->orX(
                        $qb->expr()->like('O.billingAddress', ':phone_' . $fraud->getId()),
                        $qb->expr()->like('O.shippingAddress', ':phone_' . $fraud->getId())
                    );
                    $qb->setParameter('phone_' . $fraud->getId(), '%' . $fraud->getPhoneNumber() . '%');
                }

                if (!empty($fraud->getAddressLine1())) {
                    $conditions[] = $qb->expr()->orX(
                        $qb->expr()->like('O.billingAddress', ':addressLine1_' . $fraud->getId()),
                        $qb->expr()->like('O.shippingAddress', ':addressLine1_' . $fraud->getId())
                    );
                    $qb->setParameter('addressLine1_' . $fraud->getId(), '%' . $fraud->getAddressLine1() . '%');
                }

                if (!empty($fraud->getLastName())) {
                    $conditions[] = $qb->expr()->orX(
                        $qb->expr()->like('O.billingAddress', ':lastName_' . $fraud->getId()),
                        $qb->expr()->like('O.shippingAddress', ':lastName_' . $fraud->getId())
                    );
                    $qb->setParameter('lastName_' . $fraud->getId(), '%' . $fraud->getLastName() . '%');
                }

                if (!empty($fraud->getFirstName())) {
                    $conditions[] = $qb->expr()->orX(
                        $qb->expr()->like('O.billingAddress', ':firstName_' . $fraud->getId()),
                        $qb->expr()->like('O.shippingAddress', ':firstName_' . $fraud->getId())
                    );
                    $qb->setParameter('firstName_' . $fraud->getId(), '%' . $fraud->getFirstName() . '%');
                }
            }
            if (!empty($conditions)) {
                $orX->add(new Orx($conditions));
            }
        }

        $qb->where($orX);

        if ($getResult) {
            return $qb->getQuery()->getResult();
        }
        return $qb->getQuery();
    }

    public function getReadyForShipment(): Query
    {
        $qb = $this->createQueryBuilder('O');
        $qb->andWhere($qb->expr()->isNull('O.shippingStatus'));
        $qb->andWhere($qb->expr()->eq('O.status', ':status'));
        $qb->setParameter('status', OrderStatusEnum::READY_FOR_SHIPMENT);
        $qb->orderBy('O.createdAt', 'ASC');
        return $qb->getQuery();

    }

    public function filterOrderSelective(
        ?array              $status = [],
        string|array        $paymentStatus = [],
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $endDate = null,
        bool                $canceledOrders = false,
        bool                $result = false,
        string              $orderByField = 'id',
        string              $orderBy = 'DESC'
    ): Query|array
    {
        $qb = $this->createQueryBuilder('O');
        if (!in_array(OrderStatusEnum::CREATED, $status)) {
            $qb->andWhere($qb->expr()->neq('O.status', ':created'));
            $qb->setParameter('created', OrderStatusEnum::CREATED);
        }

        $qb->andWhere($qb->expr()->notIn('O.paymentStatus', ':failedOrders'));
        $qb->setParameter('failedOrders', [PaymentStatusEnum::INITIATED, PaymentStatusEnum::PROCESSING, PaymentStatusEnum::FAILED]);

        // excluding all cancelled orders
        if (!$canceledOrders) {
            $qb->andWhere($qb->expr()->neq('O.status', ':cancelled'));
            $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);
        }

        if ($fromDate) {
            $qb->andWhere($qb->expr()->gte('O.orderAt', ':fromDate'));
            $qb->setParameter('fromDate', $fromDate->setTime(0, 0, 0));
        }

        if ($endDate) {
            $qb->andWhere($qb->expr()->lte('O.orderAt', ':endDate'));
            $qb->setParameter('endDate', $endDate->setTime(23, 59, 59));
        }

        if (array_filter($status)) {
            $qb->andWhere($qb->expr()->in('O.status', ':status'));
            $qb->setParameter('status', $status);
        }

        if ($paymentStatus) {
            if (is_array($paymentStatus)) {
                $qb->andWhere($qb->expr()->in('O.paymentStatus', ':paymentStatus'));
            } else {
                $qb->andWhere($qb->expr()->eq('O.paymentStatus', ':paymentStatus'));
            }
            $qb->setParameter('paymentStatus', $paymentStatus);
        }

        $qb->andWhere($qb->expr()->notIn('OI.itemType', ':itemType'));
        $qb->setParameter('itemType', ['CHARGED_ITEM', 'DISCOUNT_ITEM']);

        $qb->leftJoin('O.orderItems', 'OI');
        $qb->leftJoin('OI.product', 'P');
        $qb->leftJoin('P.parent', 'P1');
        $qb->addSelect('OI', 'P');
        $qb->select([
            'O.orderId AS orderId',
            'O.status',
            'O.paymentStatus',
            'O.orderAt',
            'O.paymentMethod',
            'O.orderProtectionAmount',
            'O.isSuperRush',
            'OI.id AS orderItemId',
            'OI.addOns',
            'OI.metaData',
            'OI.quantity',
            'P.name AS name',
            'P.sku AS sku',
            'P1.sku AS parentSku',
        ]);
        $qb->orderBy('O.' . $orderByField, $orderBy);

        $query = $qb->getQuery();

        if ($result) {
            $orders = [];
            foreach ($query->getArrayResult() as $row) {
                $orderId = $row['orderId'];
                $orders[$orderId] ??= [
                    'orderId' => $orderId,
                    'orderAt' => $row['orderAt'],
                    'orderItems' => []
                ];

                $orders[$orderId]['orderItems'][] = [
                    'id' => $row['orderItemId'],
                    'addOns' => $row['addOns'],
                    'metaData' => $row['metaData'],
                    'quantity' => $row['quantity'],
                    'orderId' => $orderId,
                    'product' => [
                        'name' => $row['name'],
                        'sku' => $row['sku'],
                        'parentSku' => $row['parentSku'],
                    ]
                ];
            }

            return array_values($orders);
        }

        return $query;
    }

    public function ordersToBeMarkedAsCompleted()
    {
        // mark order completed which shipingDate is 10days or olders

        $qb = $this->createQueryBuilder('O');

        $qb->andWhere($qb->expr()->isNotNull('O.shippingDate'));
        $qb->andWhere($qb->expr()->lt('O.shippingDate', ':shippingDate'));
        $qb->setParameter('shippingDate', new \DateTimeImmutable('-10 days'));

        $qb->andWhere($qb->expr()->in('O.status', ':status'));
        $qb->setParameter('status', [OrderStatusEnum::SHIPPED]);

        return $qb->getQuery()->getResult();
    }

    public function ordersMarkedAsReadyForShippment(): array
    {
        $qb = $this->createQueryBuilder('O');

        $qb->select('O.shippingOrderId, O.orderId')
            ->andWhere($qb->expr()->in('O.status', ':status'))
            ->setParameter('status', [OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SENT_FOR_PRODUCTION])
            ->andWhere($qb->expr()->isNotNull('O.shippingOrderId'));

        return $qb->getQuery()->getArrayResult();
    }
    public function findOrderByOrderId(string $orderId): array
    {
        $qb = $this->createQueryBuilder('O');

        $qb->andWhere($qb->expr()->like('O.orderId', ':orderId'))
            ->setParameter('orderId', $orderId . '%');

        return $qb->getQuery()->getResult();
    }

    public function findOrdersByTrackingNumber(string $trackingId, bool $isPromoStore): array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->leftJoin('O.orderShipments', 'OS')
        ->leftJoin('O.storeDomain', 'sd')
        ->addSelect('sd')
        ->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('O.shippingTrackingId', ':trackingId'),
                $qb->expr()->like('OS.trackingId', ':trackingId')
            )
        )
        ->setParameter('trackingId', '%' . $trackingId . '%')
        ->orderBy('O.id', 'DESC');

        if ($isPromoStore) {
            $qb->andWhere('sd.name = :promo')
            ->setParameter('promo', 'Promo');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('sd.name'),
                    $qb->expr()->neq('sd.name', ':promo')
                )
            )->setParameter('promo', 'Promo');
        }

        return $qb->getQuery()->getResult();
    }

    public function getOrder(string|int $orderId): ?Order
    {
        $qb = $this->createQueryBuilder('O');
        $qb->andWhere($qb->expr()->eq('O.orderId', ':orderId'));
        $qb->setParameter('orderId', $orderId);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getOrdersForWarehouse(string $query): array
    {
        $qb = $this->createQueryBuilder('O');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->in('O.status', [OrderStatusEnum::SENT_FOR_PRODUCTION, OrderStatusEnum::READY_FOR_SHIPMENT, OrderStatusEnum::SHIPPED]),
            $qb->expr()->eq('O.isFreightRequired', true)
        ));

        if ($query) {
            $qb->andWhere($qb->expr()->like('O.orderId', ':query'));
            $qb->setParameter('query', '%' . $query . '%');
        }

        $qb->setMaxResults(15);
        $qb->orderBy('O.orderAt', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function findUniqueOrdersByFile(int $invoiceFileId): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.shippingInvoices', 'si')
            ->where('si.file = :fileId')
            ->setParameter('fileId', $invoiceFileId)
            ->groupBy('o.id') 
            ->getQuery()
            ->getResult();
    }

    public function findAssignedOrderByUser($user): ?Order
    {
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->andWhere($qb->expr()->eq('o.proofDesigner', ':user'))
            ->andWhere($qb->expr()->eq('o.status', ':status'))
            ->setParameter('user', $user)
            ->setParameter('status', OrderStatusEnum::DESIGNER_ASSIGNED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * Find orders by date range with batch processing support
     * Uses orderAt field and handles soft deletes
     * 
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $limit
     * @param int $offset
     * @return Order[]
     */
    public function findOrdersByDateRange(
        \DateTime $startDate,
        \DateTime $endDate,
        int $limit,
        int $offset
    ): array {
        $start = clone $startDate;
        $end = clone $endDate;

        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('o');
        
        return $qb
            ->select('o', 'oi', 'p', 's')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.store', 's')
            ->where('o.orderAt >= :startDate')
            ->andWhere('o.orderAt <= :endDate')
            ->andWhere('o.deletedAt IS NULL')
            ->setParameter('startDate', $start->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $end->format('Y-m-d H:i:s'))
            ->orderBy('o.orderAt', 'DESC')
            ->addOrderBy('o.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count orders by date range (excludes soft deleted)
     * 
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return int
     */
    public function countOrdersByDateRange(
        \DateTime $startDate,
        \DateTime $endDate
    ): int {
        $start = clone $startDate;
        $end = clone $endDate;

        $start->setTime(0, 0, 0);

        $end->setTime(23, 59, 59);
        
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.orderAt >= :startDate')
            ->andWhere('o.orderAt <= :endDate')
            ->andWhere('o.deletedAt IS NULL')
            ->setParameter('startDate', $start->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $end->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get customer trend analysis data
     * Groups orders by customer email from billingAddress
     * Handles both guest and registered users
     */
    public function getCustomerTrendAnalysis(
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $start = clone $startDate;
        $end = clone $endDate;

        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->where('o.orderAt >= :startDate')
            ->andWhere('o.orderAt <= :endDate')
            ->andWhere('o.deletedAt IS NULL')
            ->setParameter('startDate', $start->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $end->format('Y-m-d H:i:s'))
            ->getQuery();

        $orders = $qb->getResult();

        $customerData = [];
        foreach ($orders as $order) {
            $billingAddress = $order->getBillingAddress();
            $email = $billingAddress['email'] ?? 'no-email-' . $order->getId();
            
            if (!isset($customerData[$email])) {
                $firstName = $billingAddress['firstName'] ?? '';
                $lastName = $billingAddress['lastName'] ?? '';
                $customerName = trim($firstName . ' ' . $lastName);
                
                $customerData[$email] = [
                    'customer_email' => $email === 'no-email-' . $order->getId() ? 'N/A' : $email,
                    'customer_name' => !empty($customerName) ? $customerName : 'N/A',
                    'customer_phone' => $billingAddress['phone'] ?? 'N/A',
                    'customer_city' => $billingAddress['city'] ?? 'N/A',
                    'customer_state' => $billingAddress['state'] ?? 'N/A',
                    'customer_country' => $billingAddress['country'] ?? 'N/A',
                    'total_orders' => 0,
                    'total_spent' => 0,
                    'first_order_date' => $order->getOrderAt(),
                    'last_order_date' => $order->getOrderAt(),
                    'order_ids' => [],
                ];
            }
            
            $customerData[$email]['total_orders']++;
            $customerData[$email]['total_spent'] += (float)$order->getTotalAmount();
            $customerData[$email]['order_ids'][] = $order->getOrderId();
            
            if ($order->getOrderAt() < $customerData[$email]['first_order_date']) {
                $customerData[$email]['first_order_date'] = $order->getOrderAt();
            }
            
            if ($order->getOrderAt() > $customerData[$email]['last_order_date']) {
                $customerData[$email]['last_order_date'] = $order->getOrderAt();
            }
        }

        foreach ($customerData as &$data) {
            $data['average_order_value'] = $data['total_orders'] > 0 
                ? $data['total_spent'] / $data['total_orders'] 
                : 0;

            if ($data['total_orders'] >= 5) {
                $data['customer_type'] = 'VIP';
            } elseif ($data['total_orders'] > 1) {
                $data['customer_type'] = 'Repeat';
            } else {
                $data['customer_type'] = 'One-time';
            }

            if ($data['total_orders'] > 1) {
                $daysDiff = $data['first_order_date']->diff($data['last_order_date'])->days;
                $data['avg_days_between_orders'] = $daysDiff / ($data['total_orders'] - 1);
            } else {
                $data['avg_days_between_orders'] = 0;
            }
        }

        usort($customerData, function($a, $b) {
            return $b['total_spent'] <=> $a['total_spent'];
        });
        
        return $customerData;
    }

    /**
     * Get orders by store
     */
    public function findOrdersByStore(
        int $storeId,
        \DateTime $startDate,
        \DateTime $endDate,
        int $limit,
        int $offset
    ): array {
        $start = clone $startDate;
        $end = clone $endDate;

        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);
        
        return $this->createQueryBuilder('o')
            ->select('o', 'oi', 'p')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->where('o.orderAt >= :startDate')
            ->andWhere('o.orderAt <= :endDate')
            ->andWhere('o.deletedAt IS NULL')
            ->andWhere('o.store = :storeId')
            ->setParameter('startDate', $start->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $end->format('Y-m-d H:i:s'))
            ->setParameter('storeId', $storeId)
            ->orderBy('o.orderAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }


    public function findTrackOrder($orderId, bool $isPromoStore)
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.storeDomain', 'sd')
            ->addSelect('sd')
            ->andWhere('o.orderId = :orderId')
            ->setParameter('orderId', $orderId);

        if ($isPromoStore) {
            $qb->andWhere('sd.name = :promo');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('sd.name'),
                    $qb->expr()->neq('sd.name', ':promo')
                )
            );
        }

        $qb->setParameter('promo', 'Promo');

        return $qb->getQuery()->getOneOrNullResult();
    }

    //    /**
//     * @return Order[] Returns an array of Order objects
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

//    public function findOneBySomeField($value): ?Order
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}