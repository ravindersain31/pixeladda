<?php

namespace App\Twig;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\Store;
use App\Repository\CartRepository;
use App\Service\CartManagerService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LightCartProvider
{

    private string|null $cartId;

    private ?array $cart;

    public function __construct(
        private readonly CartRepository $repository,
        private readonly CartManagerService $cartManagerService,
        private readonly RequestStack $requestStack
    )
    {
        $request = $this->requestStack->getCurrentRequest();
        $defaultCartId = $_COOKIE['cartId'] ?? null;
        if ($request instanceof Request && $request->attributes->get('_route') === 'cart') {
            $cartId = $request->get('id', $defaultCartId);
        } else {
            $cartId = $defaultCartId;
        }

        $this->cartId = $cartId;
        $this->cart = $this->repository->findLightCart($this->cartId);
    }

    public function getCartId(): string|null
    {
        return $this->cartId;
    }

    public function getTotalQuantity(): int
    {
        return $this->cart['total']['quantity'] ?? 0;
    }

    public function getTotalAmount(): float
    {
        return $this->cart['total']['amount'] ?? 0;
    }

    public function getItems(): array
    {
        return $this->cart['items'] ?? [];
    }

    public function getCartTotalAmount(): float
    {
        return $this->cart['total']['cartTotalAmount'] ?? 0;
    }

    public function getCart(): array
    {
        return $this->cart;
    }

    public function setCartCookie(string $cartId): void
    {
        $expire = (new \DateTimeImmutable('now'))->modify("+7 day");
        setcookie('cartId', $cartId, $expire->getTimestamp(), '/');
    }

}