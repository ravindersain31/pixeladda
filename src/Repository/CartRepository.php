<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\EmailQuote;
use App\Entity\Order;
use App\Entity\SavedCart;
use App\Entity\SavedDesign;
use App\Enum\DBInstanceEnum;
use App\Trait\EntityManagerInstanceTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 *
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository
{
    use EntityManagerInstanceTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
        $this->setManagerRegistry($registry);
    }

    public function save(Cart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLightCart(string|null $cartId): ?array
    {
        if ($cartId === null) {
            return null;
        }
        $items = $this->findLightCartItems($cartId);
        $totalQuantity = 0;
        $totalAmount = 0;
        foreach ($items as $key => $item) {
            if (isset($item['data']['isCustomSize']) && $item['data']['isCustomSize'] && $item['data']['customSize']['templateSize']) {
                $name = $item['data']['customSize']['templateSize']['width'] . "x" . $item['data']['customSize']['templateSize']['height'];
                $image = $item['data']['customSize']['image'];
            } else {
                $name = $item['data']['name'];
                $image = $item['data']['image'];
            }
            $totalQuantity += $item['quantity'];
            $items[$key]['data'] = [
                "image" => $image,
                "label" => $item['variantLabel'],
                "sku" => $item['data']['sku'],
                "name" => $name,
                "price" => $item['data']['price'],
                "unitAmount" => $item['data']['unitAmount'],
                "totalAmount" => $item['data']['totalAmount'],
                "unitAddOnsAmount" => $item['data']['unitAddOnsAmount'],
                "customSize" => $item['data']['customSize'] ?? null,
                "productTypeSlug" => $item['productTypeSlug'],

            ];
            $totalAmount += $item['data']['totalAmount'];
        }
        return [
            'items' => $items,
            'total' => [
                'quantity' => $totalQuantity,
                'amount' => $totalAmount,
                'cartTotalAmount' => $this->findCartTotal($cartId)
            ],
        ];
    }

    public function findLightCartItems(string $cartId): ?array
    {

        $qb = $this->createQueryBuilder('C', dbInstance: DBInstanceEnum::READER);
        $qb->join('C.cartItems', 'CI');
        $qb->join('CI.product', 'CIV');
        $qb->join('CIV.parent', 'CIP');
        $qb->join('CIP.primaryCategory', 'CIPC');
        $qb->join('CIP.productType', 'CIPT');

        $qb->select('CI.id');
        $qb->addSelect('CI.itemId');
        $qb->addSelect('CI.quantity');
        $qb->addSelect('CI.data');
        $qb->addSelect('CIV.name as variantName');
        $qb->addSelect('CIV.label as variantLabel');
        $qb->addSelect('CIP.id as productId');
        $qb->addSelect('CIP.name as productName');
        $qb->addSelect('CIP.slug as productSlug');
        $qb->addSelect('CIPC.id as categoryId');
        $qb->addSelect('CIPC.name as categoryName');
        $qb->addSelect('CIPC.slug as categorySlug');
        $qb->addSelect('CIP.sku as parentSku');
        $qb->addSelect('CIPT.slug as productTypeSlug');


        $qb->where($qb->expr()->eq('C.cartId', ':cartId'));
        $qb->setParameter('cartId', $cartId);

        return $qb->getQuery()->getResult();
    }

    public function findCartTotal(string $cartId): ?float
    {
        $qb = $this->createQueryBuilder('C', dbInstance: DBInstanceEnum::READER);
        $qb->select('C.totalAmount');
        $qb->where($qb->expr()->eq('C.cartId', ':cartId'));
        $qb->setParameter('cartId', $cartId);
        $qb->setMaxResults(1);
        $result = $qb->getQuery()->getOneOrNullResult();
        if (!empty($result)) {
            $totalAmount = $result['totalAmount'];
            return (float)$totalAmount;
        }
        return 0;
    }

    public function deleteCartWithZeroTotal(int $olderThanDays = 10, bool $isDelete = true): Query
    {
        $qb = $this->createQueryBuilder('C');
        if ($isDelete) {
            $qb->delete();
        }

        // Subquery to check if the cart is associated with any orders
        $subqueryOrders = $this->getEntityManager()->getRepository(Order::class)->createQueryBuilder('O')->select('IDENTITY(O.cart)')->where('O.cart = C');
        $qb->andWhere($qb->expr()->notIn('C.id', $subqueryOrders->getDQL()));

        $qb->andWhere($qb->expr()->lt('C.createdAt', ':date'));
        $qb->setParameter('date', new \DateTimeImmutable('-' . $olderThanDays . ' days'));

        $qb->andWhere($qb->expr()->eq('C.totalAmount', ':totalAmount'));
        $qb->setParameter('totalAmount', 0);

        return $qb->getQuery();
    }

    public function deleteCartNotAssociatedToOrder(int $olderThanDays = 60, bool $isDelete = true): Query
    {
        $qb = $this->createQueryBuilder('C');
        if ($isDelete) {
            $qb->delete();
        }

        // Subquery to check if the cart is associated with any orders
        $subqueryOrders = $this->getEntityManager()->getRepository(Order::class)->createQueryBuilder('O')->select('IDENTITY(O.cart)')->where('O.cart = C');
        $qb->andWhere($qb->expr()->notIn('C.id', $subqueryOrders->getDQL()));

        // Subquery to check if user saved the cart
        $subquerySavedCart = $this->getEntityManager()->getRepository(SavedCart::class)->createQueryBuilder('SC')->select('IDENTITY(SC.cart)')->where('SC.cart = C');
        $qb->andWhere($qb->expr()->notIn('C.id', $subquerySavedCart->getDQL()));

        // Subquery to check if the cart is associated with email quoted
        $subqueryEmailQuote = $this->getEntityManager()->getRepository(EmailQuote::class)->createQueryBuilder('EQ')->select('IDENTITY(EQ.cart)')->where('EQ.cart = C');
        $qb->andWhere($qb->expr()->notIn('C.id', $subqueryEmailQuote->getDQL()));

        // Subquery to check if the cart is associated with saved design
        $subquerySavedDesign = $this->getEntityManager()->getRepository(SavedDesign::class)->createQueryBuilder('SD')->select('IDENTITY(SD.cart)')->where('SD.cart = C');
        $qb->andWhere($qb->expr()->notIn('C.id', $subquerySavedDesign->getDQL()));

        $qb->andWhere($qb->expr()->lt('C.createdAt', ':date'));
        $qb->setParameter('date', new \DateTimeImmutable('-' . $olderThanDays . ' days'));

        return $qb->getQuery();
    }


//    /**
//     * @return Cart[] Returns an array of Cart objects
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

//    public function findOneBySomeField($value): ?Cart
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
