<?php

namespace App\Helper;


use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\Store;
use Doctrine\ORM\EntityManagerInterface;

class SKUGenerator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function generate(Product $product): ?string
    {
        $primaryCategory = $product->getPrimaryCategory();

        $lastProductSku = $this->entityManager->getRepository(Product::class)->getLastProductSku(category: $primaryCategory);
        return $this->getNewSKUNumber($lastProductSku, $primaryCategory->getSkuInitial());
    }

    public function getNewSKUNumber(string $lastProductSku, string $skuInitial): ?string
    {

        $lastSkuNumber = preg_replace('/[^0-9]/', '', $lastProductSku);
        $lastSkuNumber++;

        $newSku = $skuInitial . str_pad($lastSkuNumber, 4, '0', STR_PAD_LEFT);

        $isSkuExists = $this->entityManager->getRepository(Product::class)->isSkuExists($newSku);
        if ($isSkuExists) {
            return $this->getNewSKUNumber($newSku, $skuInitial);
        }
        return $newSku;
    }

    public function generateVariant(Product $product, array $existingSkusInRequest = []): string
    {
        $lastProductSku = $this->entityManager->getRepository(Product::class)->getLastProductSku($product);
        if ($lastProductSku !== 0) {
            $lastProductSku = explode('/', $lastProductSku)[1];
        }
        $lastSkuNumber = preg_replace('/[^0-9]/', '', $lastProductSku);

        $attempt = 0;
        do {
            $attempt++;
            $newSkuNumber = $lastSkuNumber + $attempt;
            $newSku = $product->getSku() . '/' . str_pad($newSkuNumber, 2, '0', STR_PAD_LEFT);
            $isSkuExistsInDatabase = $this->entityManager->getRepository(Product::class)->isSkuExists($newSku, $product);
            $isSkuExistsInRequest = in_array($newSku, $existingSkusInRequest);
        } while ($isSkuExistsInDatabase || $isSkuExistsInRequest);

        return $newSku;
    }

}