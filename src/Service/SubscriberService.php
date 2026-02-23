<?php

namespace App\Service;

use App\Entity\Subscriber;
use App\Entity\Store;
use App\Entity\StoreDomain;
use Doctrine\ORM\EntityManagerInterface;

class SubscriberService
{
    const ENQUIRY_COMING_SOON = 0;
    const ENQUIRY_SAVE_OFFER = 1;
    const ENQUIRY_SAVE_CALL_US_NOW = 2;
    const ENQUIRY_CONTACT_US = 3;
    const ENQUIRY_ENQUIRY = 4;
    const ENQUIRY_MOBILE_ALERT = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function subscribe(
        ?string            $email=null,
        ?string            $fullName = null,
        ?int               $type = null,
        ?string            $phone = null,
        ?bool              $mobileAlert = false,
        ?bool              $marketing = false,
        ?bool              $offers = false,
        Store|string|int   $store = 1,
        ?StoreDomain         $storeDomain = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ): void
    {

        try {
            $store = $store instanceof Store ? $store : $this->entityManager->getReference(Store::class, $store);
            if($email === null && isset($phone) && !empty($phone)) {
                $existingSubscriber = $this->entityManager->getRepository(Subscriber::class)->findOneBy(['phone' => $phone, 'store' => $store]);
            }else{
                $existingSubscriber = $this->entityManager->getRepository(Subscriber::class)->findOneBy(['email' => $email, 'store' => $store]);
            }
            if ($existingSubscriber instanceof Subscriber) {
                $subscription = $existingSubscriber;
            } else {
                $subscription = new Subscriber();
                $subscription->setStore($store);
            }
            if ($storeDomain instanceof StoreDomain) {
                $subscription->setStoreDomain($storeDomain);
            }

            $subscription->setType($type);

            if ($fullName) {
                $subscription->setName($fullName);
            }
            if ($email) {
                $subscription->setEmail($email);
            }
            if ($phone) {
                $subscription->setPhone($phone);
            }

            if ($existingSubscriber === null || !$existingSubscriber->getOffers()) {
                $subscription->setOffers($offers);
            }

            if ($existingSubscriber === null || !$existingSubscriber->getMarketing()) {
                $subscription->setMarketing($marketing);
            }

            if ($existingSubscriber === null || !$existingSubscriber->getMobileAlert()) {
                $subscription->setMobileAlert($mobileAlert);
            }

            if ($createdAt) {
                $subscription->setCreatedAt($createdAt);
            }

            if ($updatedAt) {
                $subscription->setUpdatedAt($updatedAt);
            }

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

        } catch (\Exception $e) {

        }
    }

    public function unsubscribe(
        string $email, 
        ?bool $marketing = false,
        ?bool $offers = false,
        ?bool $mobileAlert = false,
        Store|string|int $store = 1,
    ): array 
    {
        try {
            $store = $store instanceof Store ? $store : $this->entityManager->getReference(Store::class, $store);
            $existingSubscriber = $this->entityManager->getRepository(Subscriber::class)->findOneBy(['email' => $email, 'store' => $store]);
    
            if (!$existingSubscriber) {
                return ['status' => 'danger', 'message' => 'Subscriber not found for this email: ' . $email];
            }
    
            $messages = [];
            $alreadyUnsubscribedMessages = [];
            $finalMessage = '';
    
            if ($offers) {
                if ($existingSubscriber->getOffers()) {
                    $existingSubscriber->setOffers(false);
                    $messages[] = 'offers emails';
                } else {
                    $alreadyUnsubscribedMessages[] = 'offers emails';
                }
            }
    
            if ($marketing) {
                if ($existingSubscriber->getMarketing()) {
                    $existingSubscriber->setMarketing(false);
                    $messages[] = 'marketing emails';
                } else {
                    $alreadyUnsubscribedMessages[] = 'marketing emails';
                }
            }
    
            if ($mobileAlert) {
                if ($existingSubscriber->getMobileAlert()) {
                    $existingSubscriber->setMobileAlert(false);
                    $messages[] = 'mobile alerts';
                } else {
                    $alreadyUnsubscribedMessages[] = 'mobile alerts';
                }
            }
    
            if (!empty($messages)) {
                $this->entityManager->persist($existingSubscriber);
                $this->entityManager->flush();
            }
    
   
            if (!empty($messages)) {
                $finalMessage .= 'You have been unsubscribed from ' . implode(', ', $messages) . '. ';
            }
    
            if (!empty($alreadyUnsubscribedMessages)) {
                $finalMessage .= 'You are already unsubscribed from ' . implode(', ', $alreadyUnsubscribedMessages) . '.';
            }
            
            if (count($alreadyUnsubscribedMessages) === count(array_filter([$offers, $marketing, $mobileAlert]))) {
                return ['status' => 'info', 'message' => 'You are already unsubscribed from ' . implode(', ', $alreadyUnsubscribedMessages) . '.'];
            }
    
            return ['status' => 'success', 'message' => trim($finalMessage)];
    
        } catch (\Exception $e) {
            return ['status' => 'danger', 'message' => $e->getMessage()];
        }
    }
}