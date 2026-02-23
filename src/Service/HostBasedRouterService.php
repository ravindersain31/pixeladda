<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\RouteCollection;

class HostBasedRouterService implements RouterInterface, WarmableInterface
{
    private string $adminHost;

    public function __construct(private RouterInterface $router, private RequestStack $requestStack, ParameterBagInterface $params)
    {
        if (!$params->has('APP_ADMIN_HOST')) {
            throw new \RuntimeException('The APP_ADMIN_HOST environment variable is not set.');
        }

        $this->adminHost = $params->get('APP_ADMIN_HOST');
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        $domain = $this->getCurrentDomain();
        
        if (strcasecmp($domain, $this->adminHost) === 0) {
            return $this->router->generate($name, $parameters, $referenceType);
        }

        $url = $this->router->generate($name, $parameters, $referenceType);

        switch ($referenceType) {
            case UrlGeneratorInterface::ABSOLUTE_URL:
            case UrlGeneratorInterface::NETWORK_PATH:
                $url = preg_replace('/^(http[s]?:)?\/\/[^\/]+/', "$1//$domain", $url);
                break;
    
            case UrlGeneratorInterface::ABSOLUTE_PATH:
                $url = preg_replace('/^(http[s]?:)?\/\/[^\/]+/', '', $url);
                break;
    
            case UrlGeneratorInterface::RELATIVE_PATH:
                $url = preg_replace('/^(http[s]?:)?\/\/[^\/]+/', '', $url);
                if (strpos($url, '/') === 0) {
                    $url = '../../..' . $url;
                }
                break;
    
            default:
                break;
        }
    
        return $url;
    }

    public function match(string $pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    private function getCurrentDomain(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            throw new \RuntimeException('No current request available.');
        }

        return $request->getHost();
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if ($this->router instanceof WarmableInterface) {
            return $this->router->warmUp($cacheDir, $buildDir);
        }

        return [];
    }
    public static function replaceWithAdminHost(string $url, $params = null): string
    {
        $adminHost = null;

        if ($params !== null && $params->has('app.admin_host')) {
            $adminHost = $params->get('app.admin_host');
        } elseif (!empty($_ENV['APP_ADMIN_HOST'])) {
            $adminHost = $_ENV['APP_ADMIN_HOST'];
        }

        if (!$adminHost || !$url) {
            return $url;
        }

        return preg_replace(
            '#^https?://[^/]+#',
            'https://' . $adminHost,
            $url
        );
    }

}