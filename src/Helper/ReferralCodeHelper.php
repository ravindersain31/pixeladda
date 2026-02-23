<?php

namespace App\Helper;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Referral;
use App\Entity\User; 

class ReferralCodeHelper
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function generateUniqueReferralCode(User $user, int $length = 8): string
    {
        $userIdentifier = strtoupper(substr($user->getUsername(), 0, 3)) . $user->getId(); 
        $referralCodeBase = $userIdentifier . $this->generateRandomString($length - strlen($userIdentifier));
        
        if ($this->isReferralCodeExists($referralCodeBase)) {
            return $this->generateUniqueReferralCode($user, $length); 
        }

        return $referralCodeBase;
    }

    private function generateRandomString(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    private function isReferralCodeExists(string $referralCode): bool
    {
        return (bool) $this->entityManager
            ->getRepository(Referral::class)
            ->findOneBy(['referralCode' => $referralCode]);
    }
}
