<?php

namespace App\Mercure;

use App\Enum\Admin\WarehouseMercureEventEnum;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

final readonly class TokenProvider implements TokenProviderInterface
{

    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function getJwt(?string $domain = null): string
    {
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->parameterBag->get('MERCURE_JWT_SECRET'))
        );

        $domain = $domain ?? $this->parameterBag->get('APP_ADMIN_HOST');

        $claims = [
            'publish' => WarehouseMercureEventEnum::getTopics($domain),
            'subscribe' => WarehouseMercureEventEnum::getTopics($domain),
        ];

        return $configuration->builder()->withClaim('mercure', $claims)
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt(new \DateTimeImmutable('+3 days'))
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString();
    }
}