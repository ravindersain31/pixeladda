<?php

namespace App\Service;

use App\Entity\RequestCallBack;
use App\Entity\Store;
use Doctrine\ORM\EntityManagerInterface;

class RequestCallBackService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function requestCallBack(
        ?string           $fullName = null,
        ?string           $phone = null,
        ?string           $comment = null,
        Store|string|int  $store = 1,
        ?bool              $isOpened = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ): void {
        $store = $store instanceof Store
            ? $store
            : $this->entityManager->getReference(Store::class,  $store);

        $isExists = $this->entityManager->getRepository(RequestCallBack::class)->findOneBy([
            'phone' => $phone,
            'store' => $store
        ]);

        if($isExists) return;

        $subscription = new RequestCallBack();
        $subscription->setName($fullName);
        $subscription->setStore($store);
        $subscription->setComment($comment);
        $subscription->setPhone($phone);

        if($isOpened !== null) {
            $subscription->setIsOpened($isOpened);
        }

        if($createdAt){
            $subscription->setCreatedAt($createdAt);
        }

        if ($updatedAt) {
            $subscription->setUpdatedAt($updatedAt);
        }

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

}