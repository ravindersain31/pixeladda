<?php

namespace App\EventListener;

use App\Entity\CustomerPhotos;
use App\Entity\Product;
use App\Enum\Admin\CacheEnum;
use App\Service\CacheService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class CacheListener
{
    public function __construct(private readonly CacheService $cacheService) 
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->handleCacheClear($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->handleCacheClear($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->handleCacheClear($args->getObject());
    }

    private function handleCacheClear(object $entity): void
    {
       $pool = match (true) {
            $entity instanceof CustomerPhotos => CacheEnum::CUSTOMER_PHOTOS->value,
            $entity instanceof Product => CacheEnum::PRODUCT->value,
            default => null
        };

        if ($pool === null) {
            return;
        }

        $this->cacheService->clearPool($pool);
    }
}