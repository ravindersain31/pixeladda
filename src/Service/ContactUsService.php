<?php

namespace App\Service;

use App\Entity\ContactUs;
use App\Entity\Store;
use App\Repository\StoreDomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class ContactUsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private readonly StoreDomainRepository  $storeDomainRepository,
    ) {
    }

    public function contactUs(
        string            $email,
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

        $isExists = $this->entityManager->getRepository(ContactUs::class)->findOneBy([
            'email' => $email,
            'store' => $store,
            'isOpened' => true,
        ]);

        if($isExists) return;
        $host = $this->requestStack->getCurrentRequest()->getHost();
        $storeDomain = $this->storeDomainRepository->findOneBy(['domain' => $host]);

        $subscription = new ContactUs();
        $subscription->setName($fullName);
        $subscription->setEmail($email);
        $subscription->setStore($store);
        $subscription->setComment($comment);
        $subscription->setPhone($phone);
        $subscription->setStoreDomain($storeDomain ?: null);

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