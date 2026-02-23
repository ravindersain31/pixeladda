<?php

namespace App\Repository;

use App\Entity\StoreSettings;
use App\Entity\Store;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreSettings>
 *
 * @method Settings|null find($id, $lockMode = null, $lockVersion = null)
 * @method Settings|null findOneBy(array $criteria, array $orderBy = null)
 * @method Settings[]    findAll()
 * @method Settings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreSettings::class);
    }

    public function save(StoreSettings $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StoreSettings $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function listSettings($store): Query
    {
        $qb = $this->createQueryBuilder('S');
        $qb->where('S.store = :store');
        $qb->setParameter('store', $store);
        return $qb->getQuery();
    }

    public function getDailyCapacity(): int
    {
        $dailyCapacity = $this->getEntityManager()->getRepository(StoreSettings::class)->findOneBy(['settingKey' => 'daily_capacity'])?->getValue() ?? 0;
        return $dailyCapacity;
    }

    public function getTotalDaysRequiredUnmadeSigns(array $status = [])
    {
        $totalUnmadeSigns = $this->getEntityManager()->getRepository(Order::class)->getTotalQuantityOfOrders($status);
        $dailyCapacity = $this->getDailyCapacity();

        if($totalUnmadeSigns == 0 || $dailyCapacity == 0){
            return $result = 0;
        }
        $result = $totalUnmadeSigns / $dailyCapacity;

        return (int) ceil($result);
    }
}
