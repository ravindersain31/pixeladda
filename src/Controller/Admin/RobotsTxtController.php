<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RobotsTxtController extends AbstractController
{
    #[Route(path: '/robots.txt', name: 'robots_txt')]
    public function category(): Response
    {
        return new Response(
            "User-agent: *\nDisallow: /",
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
    }

}