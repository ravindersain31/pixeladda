<?php

namespace App\Service;

use App\Entity\BulkOrder;
use App\Entity\Store;
use App\Entity\StoreDomain;
use Doctrine\ORM\EntityManagerInterface;

readonly class BulkOrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function createBulkOrder(
        string               $email,
        ?string              $firstName = null,
        ?string              $lastName = null,
        ?string              $phoneNumber = null,
        ?string              $company = null,
        ?int                 $quantity = null,
        ?string              $budget = null,
        ?\DateTimeInterface  $deliveryDate = null,
        ?string              $productInInterested = null,
        ?string              $comment = null,
        ?int                 $status = BulkOrder::STATUS_OPEN,
        Store|string|int     $store = 1,
        ?\DateTimeImmutable  $createdAt = null,
        ?\DateTimeImmutable  $updatedAt = null,
        ?StoreDomain         $storeDomain = null,
    ): void {
        $store = $store instanceof Store
            ? $store
            : $this->entityManager->getReference(Store::class,  $store);

        $bulkOrder = new BulkOrder();
        $bulkOrder->setFirstName($firstName);
        $bulkOrder->setLastName($lastName);
        $bulkOrder->setEmail($email);
        $bulkOrder->setPhoneNumber($phoneNumber);
        $bulkOrder->setCompany($company);
        $bulkOrder->setQuantity($quantity);
        $bulkOrder->setBudget($budget);
        $bulkOrder->setDeliveryDate($deliveryDate);
        $bulkOrder->setProductInInterested($productInInterested);
        $bulkOrder->setComment($comment);
        $bulkOrder->setStatus($status ?? BulkOrder::STATUS_OPEN);
        $bulkOrder->setStore($store);
        $bulkOrder->setStoreDomain($storeDomain);
        $now = new \DateTimeImmutable();
        $bulkOrder->setCreatedAt($createdAt ?? $now);
        $bulkOrder->setUpdatedAt($updatedAt ?? $now);

        $this->entityManager->persist($bulkOrder);
        $this->entityManager->flush();
    }
}