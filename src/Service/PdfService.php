<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Environment;
use App\Service\StoreInfoService;
use App\Service\CartManagerService;


class PdfService extends AbstractController
{
    public function __construct(
        private Environment $twig,
        private StoreInfoService $storeInfoService,
        private CartManagerService $cartManagerService,
    ) {}

    public function generateQuotePdf($editorData): string
    {

        $cart = $this->cartManagerService->createCart();
        $cart = $this->cartManagerService->updateCart($cart, $editorData);

        $storeInfo = $this->storeInfoService->storeInfo();

        if ($storeInfo['isPromoStore']) {
            $primary = '#25549b';
        } else {
            $primary = '#6f4c9e';
        }


        $html = $this->twig->render('cart/pdf_quote.html.twig', [
            'items' => $cart->getCartItems(),
            'cart' => $cart,
            'quoteDate' => (new \DateTime())->format('d M Y h:i A'),
            'storeInfo' => $storeInfo,
            'primary' => $primary,

        ]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output(); 
    }
}
