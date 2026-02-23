<?php 

namespace App\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HostBasedUrlExtension extends AbstractExtension
{
    private string $adminHost;

    public function __construct(private RequestStack $requestStack, private RouterInterface $router, ParameterBagInterface $params)
    {
        if (!$params->has('APP_ADMIN_HOST')) {
            throw new \RuntimeException('The APP_ADMIN_HOST environment variable is not set.');
        }

        $this->adminHost = $params->get('APP_ADMIN_HOST');
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('url', [$this, 'getUrl']),
            new TwigFunction('path', [$this, 'getPath']),
        ];
    }

    public function getUrl($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_URL)
    {
        return $this->generateHostBasedUrl($name, $parameters, $referenceType);
    }

    public function getPath($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->generateHostBasedUrl($name, $parameters, $referenceType);
    }

    public function generateHostBasedUrl(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        $currentDomain = $this->getCurrentDomain();
        $generatedUrl = $this->router->generate($name, $parameters, $referenceType);

        if (strcasecmp($currentDomain, $this->adminHost) === 0) {
            return $generatedUrl;
        }

        switch ($referenceType) {
            case UrlGeneratorInterface::ABSOLUTE_URL:
            case UrlGeneratorInterface::NETWORK_PATH:
                return preg_replace('/^(http[s]?:)?\/\/[^\/]+/', "$1//$currentDomain", $generatedUrl);

            case UrlGeneratorInterface::ABSOLUTE_PATH:
            case UrlGeneratorInterface::RELATIVE_PATH:
                $path = parse_url($generatedUrl, PHP_URL_PATH) ?? '';
                $query = parse_url($generatedUrl, PHP_URL_QUERY);
                $queryString = $query ? "?$query" : ''; 
            
                return $path . $queryString;
                
            default:
                return $generatedUrl; 
        }
    }

    private function getCurrentDomain()
    {
        $request = $this->requestStack->getCurrentRequest();
        $host = $request->getHost();

        if (!$host) {
            throw new \RuntimeException('No current request available.');
        }
        
        return $host;
    }
}