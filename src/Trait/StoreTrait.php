<?php

namespace App\Trait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait StoreTrait
{
    private Request|null $request = null;

    private array $store = [];

    public function __construct(private readonly RequestStack $requestStack, public readonly EventDispatcherInterface $eventDispatcher)
    {
        $this->request = $requestStack->getCurrentRequest();
        if ($this->request) {
            $store = $this->request->get('store');
            if (is_array($store)) {
                $this->store = $this->request->get('store');
            }
        }
    }

    public function getStore(): object
    {
        return (object)$this->store;
    }

}