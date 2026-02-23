<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        date_default_timezone_set('America/Chicago');
        $cartId = $this->getCartIdCookie();
        if ($cartId) {
            $this->setCartCookie($cartId);
        }
    }

    private function getCartIdCookie(): mixed
    {
        return $_COOKIE['cartId'] ?? null;
    }

    private function setCartCookie(string $cartId): void
    {
        $expire = (new \DateTimeImmutable('now'))->modify("+7 day");
        setcookie('cartId', $cartId, $expire->getTimestamp(), '/');
    }
}
