<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use App\Enum\Admin\CacheEnum;

readonly class CacheService
{
    public function __construct(
        private CacheItemPoolInterface $defaultPool,
        private CacheItemPoolInterface $productPool,
        private CacheItemPoolInterface $categoryPool,
        private CacheItemPoolInterface $customerPhotosPool,
        private CacheItemPoolInterface $productTypePool,
        private CacheItemPoolInterface $storePool,
    ) {}

    private function getPool(?string $poolName = null): CacheItemPoolInterface
    {
        return match ($poolName) {
            CacheEnum::PRODUCT->value          => $this->productPool,
            CacheEnum::CATEGORY->value         => $this->categoryPool,
            CacheEnum::CUSTOMER_PHOTOS->value  => $this->customerPhotosPool,
            CacheEnum::PRODUCT_TYPE->value     => $this->productTypePool,
            CacheEnum::STORE->value            => $this->storePool,
            default                            => $this->defaultPool,
        };
    }

    public function getCached(string $key, callable $callback, CacheEnum $poolEnum): mixed
    {
        $key = $this->sanitizeCacheKey($key);
        $pool = $this->getPool($poolEnum->value);
        $item = $pool->getItem($key);
        
        if (!$item->isHit()) {
            $item->set($callback($item));
            $item->expiresAfter($poolEnum->ttl());
            $pool->save($item);
        }

        return $item->get();
    }

    public function clearCacheItem(string $key, ?string $poolName = null): bool|string
    {
        try {
            $this->getPool($poolName)->deleteItem($key);
            return true;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function clearPool(?string $poolName = null): bool|string
    {
        try {
            $this->getPool($poolName)->clear();
            return true;
        } catch (\Throwable $e) {
            return $e->getMessage(); 
        }
    }

    public function clearAllPools(): array
    {
        $pools = [
            'default' => $this->defaultPool,
            'product' => $this->productPool,
            'category' => $this->categoryPool,
            'customer_photos' => $this->customerPhotosPool,
            'product_type' => $this->productTypePool,
            'store' => $this->storePool,
        ];

        $errors = [];

        foreach ($pools as $name => $pool) {
            try {
                $pool->clear();
            } catch (\Throwable $e) {
                $errors[] = sprintf('Pool "%s" failed: %s', $name, $e->getMessage());
            }
        }

        return $errors;
    }

    public function getCategoryCacheKey(string $slug, string $prefix = 'category_slug'): string
    {
        return $this->sanitizeCacheKey($prefix . '_' . strtolower(trim($slug)));
    }

    public function getProductTypeCacheKey(string $slug): string
    {
        return $this->sanitizeCacheKey('product_type_by_slug_' . strtolower(trim($slug)));
    }

    public function getStoreCacheKey(string $host): string
    {
        return $this->sanitizeCacheKey('store_by_host_' . strtolower(trim($host)));
    }

    public function sanitizeCacheKey(string $key): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);

        $key = preg_replace('/_+/', '_', $key);

        return trim($key, '_');
    }

}
