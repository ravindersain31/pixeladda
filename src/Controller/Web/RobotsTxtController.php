<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RobotsTxtController extends AbstractController
{
    #[Route(path: '/robots.txt', name: 'robots_txt')]
    public function category(ParameterBagInterface $parameterBag): Response
    {
        return new Response(
            $this->getRobotsContent($parameterBag->get('CONTENT_ENV')),
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
    }

    private function getRobotsContent($env): string
    {
        $content = $this->devRobots();;
        if ($env === 'prod') {
            $content = $this->prodRobots();
        }
        return trim(preg_replace('/(?<!\n) +| +(?!\n)/', '', $content));
    }

    private function prodRobots(): string
    {
        return "
        User-agent: *
        Disallow: /my-account
        Disallow: /cart
        Disallow: /user
        Disallow: /order-confirmation
        Disallow: /track-order
        Disallow: /search?
        Disallow: /*custom=*
        Disallow: /*category=*
        ";
    }

    private function devRobots(): string
    {
        return "
        User-agent: *
        Disallow: /
        ";
    }

}