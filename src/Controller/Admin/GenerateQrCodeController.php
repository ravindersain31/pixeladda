<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GenerateQrCodeController extends AbstractController
{
    #[Route('/generate-qr-code', name: 'generate_qr_code')]
    public function index(): Response
    {
        return $this->render('admin/generate-qr-code/index.html.twig');
    }
}