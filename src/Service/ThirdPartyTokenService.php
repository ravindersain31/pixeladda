<?php

namespace App\Service;

use App\Entity\ThirdPartyToken;
use Doctrine\ORM\EntityManagerInterface;

class ThirdPartyTokenService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getBingAdsRefreshToken(): ?string
    {
        return $this->getToken(ThirdPartyToken::PROVIDER_BING_ADS, ThirdPartyToken::TYPE_REFRESH, ThirdPartyToken::USED_FOR_BING_ADS)->getToken();
    }

    public function saveBingAdsRefreshToken(string $refreshToken): void
    {
        $token = $this->getToken(ThirdPartyToken::PROVIDER_BING_ADS, ThirdPartyToken::TYPE_REFRESH, ThirdPartyToken::USED_FOR_BING_ADS);
        $token->setToken($refreshToken);
        $token->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function getFacebookAccessToken(): ?string
    {
        $token = $this->getToken(ThirdPartyToken::PROVIDER_FACEBOOK_ADS, ThirdPartyToken::TYPE_REFRESH, ThirdPartyToken::USED_FOR_FACEBOOK_ADS);
        return $token->getToken();
    }

    public function saveFacebookAccessToken(string $accessToken, ?string $expiresAt = null): void
    {
        $token = $this->getToken(ThirdPartyToken::PROVIDER_FACEBOOK_ADS, ThirdPartyToken::TYPE_REFRESH, ThirdPartyToken::USED_FOR_FACEBOOK_ADS);
        $token->setToken($accessToken);
        $expiresAt = (new \DateTimeImmutable())->setTimestamp($expiresAt);
        $token->setExpireAt($expiresAt);
        $token->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    private function getToken(string $provider, string $type, ?string $usedFor = null)
    {
        $filter = ['provider' => $provider, 'type' => $type];
        if ($usedFor) {
            $filter['usedFor'] = $usedFor;
        }
        $token = $this->entityManager->getRepository(ThirdPartyToken::class)->findOneBy($filter);
        if (!$token instanceof ThirdPartyToken) {
            $token = new ThirdPartyToken();
            $token->setProvider($provider);
            $token->setType($type);
            if ($usedFor) {
                $token->setUsedFor($usedFor);
            }
            $this->entityManager->persist($token);
            $this->entityManager->flush();
        }
        return $token;
    }
}