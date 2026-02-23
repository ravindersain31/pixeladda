<?php

namespace App\EventSubscriber;

use App\Repository\StoreRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StoreEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly StoreRepository $storeRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(KernelEvent $event)
    {
        $request = $event->getRequest();
        // $host = $request->getHost();
//        if (filter_var($host, FILTER_VALIDATE_IP))
//            return;
        $host = 'local.yardsignplus.com';
        $path = $request->getPathInfo();
        if ($path === '/health' || $path === '/health/') {
            return;
        }

        // Console/CLI commands don't have Domain info
        if ($request === null)
            return;

        if ($request->attributes->has('store'))
            return;

        if (str_contains($host, 'admin') || str_contains($host, 'vb-cp'))
            return;


        // dd($host);
        $store = $this->storeRepository->getStoreByHost($host);
        // dd($store);
        if ($store === null)
            throw new \RuntimeException(sprintf("Cannot find store with host %s", $host));

        $request->attributes->set('store', $store);
    }
}