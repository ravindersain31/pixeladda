<?php

namespace App\Controller\Web;

use App\Entity\Admin\Coupon;
use App\Entity\CartItem;
use App\Entity\ProductType;
use App\Entity\SavedCart;
use App\Entity\SavedDesign;
use App\Enum\CouponTypeEnum;
use App\Form\NeedProofType;
use App\Helper\OrderSampleHelper;
use App\Helper\ShippingChartHelper;
use App\Payment\AmazonPay\AmazonPay;
use App\Repository\CartItemRepository;
use App\Repository\SavedCartRepository;
use App\Repository\SavedDesignRepository;
use App\Service\CartManagerService;
use App\Service\CartPriceManagerService;
use App\Service\CartValidationService;
use App\Service\KlaviyoService;
use App\Service\PdfService;
use App\Service\RecaptchaManager;
use App\Service\SaveDesignService;
use App\Trait\StoreTrait;
use App\Service\StoreInfoService;
use App\Twig\LightCartProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment as TwigEnvironment;

#[Route(path: '/cart')]
class CartController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/', name: 'cart')]
    public function cart(Request $request, Session $session, StoreInfoService $storeInfoService, EntityManagerInterface $em, RateLimiterFactory $recaptchaFailuresLimiter, RecaptchaManager $recaptchaManager, CartManagerService $cartManagerService, EntityManagerInterface $entityManager, ShippingChartHelper $shippingChartHelper, OrderSampleHelper $orderSampleHelper, AmazonPay $amazonPay): Response
    {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $limiter->reset();
        $session->remove('recaptcha_enabled_at');
        $cartId = $request->get('id');
        $cart = $cartManagerService->getCart($cartId);
        $cart->setInternationalShippingCharge(false);
        $earliestDate = null;
        $earliestShipping = null;
        foreach ($cart->getCartItems() as $item) {
            $shipping = $item->getData()['shipping'] ?? null;

            if ($shipping && isset($shipping['date'])) {
                $date = new \DateTime($shipping['date']);

                if (is_null($earliestDate) || $date < $earliestDate) {
                    $earliestDate = $date;
                    $earliestShipping = $shipping;
                }
            }
        }

        if ($earliestShipping) {
            $cart->setDataKey('shipping', $earliestShipping);
        }
        if ($earliestDate !== null) {
            $cart->setDataKey('deliveryDate', [
                'date' => $earliestDate->format('Y-m-d'),
            ]);
        }
        $internationalShippingCharge = $cart->isInternationalShippingCharge();
        if ($internationalShippingCharge == false) {
            $totalAmount = $cart->getTotalAmount() - $cart->getInternationalShippingChargeAmount();
            $cart->setTotalAmount($totalAmount);
            $cart->setInternationalShippingChargeAmount(0);
            $cart->setInternationalShippingCharge(false);
        }
        $entityManager->flush();
        if (!$cartId || ($cartId !== $cart->getCartId())) {
            return $this->redirectToRoute('cart', ['id' => $cart->getCartId()]);
        }
        $refCoupon = $request->get('refCoupon');
        if ($refCoupon) {
            $cartManagerService->applyCoupon($cart, $refCoupon);
        }

        $sessionCoupon = $session->get('referralCode');
        $coupon = $em->getRepository(Coupon::class)->findOneBy([
            'code' => $sessionCoupon,
            'couponType' => CouponTypeEnum::REFERRAL,
        ]);
        /** @var AppUser|null $loggedInUser */
        $loggedInUser = $this->getUser();
        if ($loggedInUser && $coupon instanceof Coupon && $coupon->getUser() === $loggedInUser) {
            $session->remove('referralCode');
            $this->addFlash('danger', 'You cannot use your own referral code.');
            $sessionCoupon = null; 
        }
        
        if ($sessionCoupon && $coupon instanceof Coupon) {
            $applied = $cartManagerService->applyCoupon($cart, $sessionCoupon);
            $cartCoupon = $cart->getCoupon();

            if ($applied || ($cartCoupon && $cartCoupon->getCode() === $sessionCoupon)) {
                $session->remove('referralCode');
                $this->addFlash('success', 'Coupon applied successfully.');
            } else {
                $session->remove('referralCode');
                $this->addFlash('danger', 'Invalid or expired coupon code.');
            }
        }


        $isSampleNeedsFreeShipping = false;
        foreach ($cart->getCartItems() as $item) {
            $shipping = $item->getDataKey('shipping') ?? [];
            if ($item->getDataKey('isSample') && (!empty($shipping['amount']) && $shipping['amount'] > 0)) {
                $isSampleNeedsFreeShipping = true;
                break;
            }
        }

        $freeShipping = $this->getFreeShipping($entityManager, $shippingChartHelper, $cart->getTotalQuantity());
        $sampleFreeShipping = $orderSampleHelper->getFreeShipping();
        $amazonPayData = $amazonPay->getSignature();
        $amazonCheckoutSessionData = null;
        $currentPath = $request->getPathInfo();
        if ($request->get('amazonCheckoutSessionId')) {
            $sessionId = $request->get('amazonCheckoutSessionId');
            $sessionChargeResult = $amazonPay->handleSessionAndCharge($sessionId, $cart->getTotalAmount(), "USD", '0', $currentPath);

            if ($sessionChargeResult['success']) {
                $amazonCheckoutSessionData = $sessionChargeResult['data'];
            } else {
                $this->addFlash('danger', $sessionChargeResult['message'] ?? 'Invalid Payment Details');
                return $this->redirectToRoute('cart');
            }
        }

        $amazonPayCheckoutData = [
            'signature' => $amazonPayData['signature'] ?? null,
            'payload' => $amazonPayData['payload'] ?? null,
            'returnUrl' => $amazonPayData['checkoutResultReturnUrl'] ?? null,
            'checkoutSession' => $amazonCheckoutSessionData,
        ];

        $storeInfo = $storeInfoService->storeInfo();
        $cartManagerService->applySub1500Discount($cart);

        $needProofForm = $this->createForm(NeedProofType::class, $cart);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'storeInfo' => $storeInfo,
            'cartId' => $cart->getCartId(),
            'items' => $cart->getCartItems(),
            'freeShipping' => $freeShipping,
            'sampleFreeShipping' => $sampleFreeShipping,
            'isSampleNeedsFreeShipping' => $isSampleNeedsFreeShipping,
            'amazonPay' => $amazonPayCheckoutData,
            'needProofForm' => $needProofForm->createView(),
        ]);
    }

    #[Route(path: '/{cartId}/preview/{itemId}', name: 'cart_item_preview')]
    public function previewItem(Request $request, CartManagerService $cartManagerService): Response
    {
        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');

        $cart = $cartManagerService->getCart($cartId);
        $item = $cart->getCartItems()->filter(fn($item) => $item->getItemId() === $itemId)->first();
        if (!$item instanceof CartItem) {
            throw $this->createNotFoundException('Item not found');
        }

        $template = $item->getProduct();
        $product = $template->getParent();

        return $this->render('cart/item-preview.html.twig', [
            'cart' => $cart,
            'item' => $item,
            'template' => $template,
            'product' => $product,
            'canvasData' => $item->getCanvasData(),
        ]);
    }

    #[Route(path: '/add-to-cart', name: 'add_to_cart')]
    public function addToCart(Request $request, CartManagerService $cartManagerService, CartValidationService $cartValidationService, SessionInterface $session, KlaviyoService $klaviyoService): Response
    {
        $cartId = $request->get('cartId');
        $editorData = $request->get('editor', []);
        $wireStakeData = $request->get('wireStake', null);
        $orderSampleData = $request->get('orderSample', null);
        $blankSignData = $request->get('blankSign', null);

        $mode = null;
        if (!empty($editorData)) {
            $mode = 'add-to-cart';
        }

        if ($blankSignData) {
            $validate = $cartValidationService->validate($blankSignData);
            $editorData = $blankSignData;
        } else if ($wireStakeData) {
            $validate = $cartValidationService->validate($wireStakeData);
            $editorData = $wireStakeData;
        } elseif ($orderSampleData) {
            $validate = $cartValidationService->validate($orderSampleData);
            $editorData = $orderSampleData;
        } else {
            $validate = $cartValidationService->validate($editorData);
        }

        if ($validate !== true) {
            return $this->json($validate);
        }


        $additionalData = $editorData['additionalData'] ?? [];
        $cart = $cartManagerService->updateCart($cartId, $editorData, $mode);

        $message = 'Successfully added to cart.';

        if (isset($editorData['isNewItem']) && !$editorData['isNewItem']) {
            $message = 'Successfully updated to cart';
        }
        if (isset($additionalData['saveDesignEmail'])) {
            $message = 'Your design has been saved successfully. You will receive an email shortly with a link to your design.';
        }
        if (isset($additionalData['orderQuoteEmail'])) {
            $message = 'Your quote has been successfully saved.';
        }
        if ($cart->getCartItems()->count() == 0) {
            $session->set('orderProtection', true);
        }

        $klaviyoService->addedToCart($cart);

        // $this->addFlash('success', $message);
        return $this->json([
            'action' => 'redirect',
            'message' => $message,
            'redirectUrl' => $this->generateUrl('cart', ['id' => $cart->getCartId()], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    #[Route(path: '/share-canvas', name: 'share_canvas')]
    public function shareCanvas(Request $request, CartManagerService $cartManagerService, UrlGeneratorInterface $urlGenerator): Response
    {
        $editorData = $request->get('editor', []);
        $currentItemId = $request->get('currentItemId', []);
        $cart = $cartManagerService->createShareCart($editorData);
        $cartItems   = $cart->getCartItems();
        $matchedItem = null;
        foreach ($cartItems as $item) {
            if ($item->getData()['productId'] === $currentItemId) {
                $matchedItem = $item;
            }
        }

        $cartId  = $cart->getCartId();
        $itemId  = $matchedItem->getId();
        $product = $matchedItem->getProduct();
        $data    = $matchedItem->getData();
        $variant = $data['templateSize']['width'] . 'x' . $data['templateSize']['height'];
        $quantity = $matchedItem->getQuantity();

        $sku = $product->getSku();
        $cleanSku = strstr($sku, '/', true) ?: $sku;

        $productTypeSlug = $product->getParent()
            ? $product->getParent()->getProductType()?->getSlug()
            : $product->getProductType()?->getSlug();

        $categorySlug = $product->getParent()
            ? $product->getParent()->getPrimaryCategory()?->getSlug()
            : $product->getPrimaryCategory()?->getSlug();

        if (!$productTypeSlug || !$categorySlug) {
            return $this->json(['error' => 'Missing product type or category slug'], 400);
        }

        $redirectUrl = $urlGenerator->generate('editor', [
            'category'    => $categorySlug,
            'productType' => $productTypeSlug,
            'sku'         => $cleanSku,
            'shareId'     => $cartId,
            'itemId'      => $itemId,
            'variant'     => $variant,
            'qty'         => $quantity,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json([
            'redirectUrl' => $redirectUrl,
        ]);
    }

    #[Route(path: '/remove-from-cart', name: 'remove_item_from_cart', methods: ['POST'])]
    public function removeItem(Request $request, CartManagerService $cartManagerService): Response
    {
        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');

        $cart = $cartManagerService->removeItem($cartId, $itemId);

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('cart', ['id' => $cart->getCartId()]);
    }

    #[Route(path: '/order-protection', name: 'toggle_order_protection', methods: ['GET'])]
    public function toggleOrderProtection(Request $request, CartManagerService $cartManagerService): Response
    {
        $redirect = $request->get('redirect');
        $redirectRoute = match ($redirect) {
            'checkout' => 'checkout',
            default => 'cart'
        };

        $cardId = $request->get('cartId');

        $isOrderProtected = $cartManagerService->updateOrderProtection($cardId);

        if ($isOrderProtected) {
            $this->addFlash('success', 'Order Protection has been successfully enabled.');
        } else {
            $this->addFlash('success', 'Order Protection has been successfully disabled.');
        }
        return $this->redirectToRoute($redirectRoute, ['id' => $cardId]);
    }

    #[Route(path: '/international-shipping-charge', name: 'international_shipping_charge', methods: ['GET'])]
    public function internationalShippingCharge(Request $request, CartManagerService $cartManagerService): Response
    {
        $redirect = $request->get('redirect');
        $redirectRoute = match ($redirect) {
            'checkout' => 'checkout',
            default => 'cart'
        };
        $cartId = $request->get('cartId');
        $isOrderProtected = $cartManagerService->updateInternationalShippingCharge($cartId);
        if ($isOrderProtected) {
            $this->addFlash('success', 'International shipping charge of $' . $cartManagerService::INTERNATIONAL_SHIPPING_CHARGE . ' has been added to your order.');
        }
        return $this->redirectToRoute($redirectRoute, ['id' => $cartId]);
    }

    #[Route(path: '/remove-coupon', name: 'remove_coupon', methods: ['GET'])]
    public function removeCoupon(Request $request, CartManagerService $cartManagerService, EntityManagerInterface $entityManager): Response
    {
        $redirect = $request->get('redirect');
        $redirectRoute = match ($redirect) {
            'checkout' => 'checkout',
            default => 'cart'
        };

        $cardId = $request->get('cartId');
        $cart = $cartManagerService->getCart($cardId);

        if (!$cart) {
            $this->addFlash('error', 'Cart not found');
        }
        if ($cart->getCoupon()) {
            $cart->setTotalAmount($cart->getTotalAmount() + $cart->getCouponAmount());
            $cart->setCoupon(null);
            $cart->setCouponAmount(0);
            $entityManager->flush();
            $this->addFlash('success', 'Coupon has been successfully removed.');
        } else {
            $this->addFlash('danger', 'No coupon found in the cart');
        }

        return $this->redirectToRoute($redirectRoute, ['id' => $cardId]);
    }

    #[Route(path: '/cart-resume', name: 'cart_resume')]
    public function cartResume(Request $request, CartManagerService $cartManagerService, LightCartProvider $lightCartProvider): Response
    {
        $cartId = $request->get('id');
        $cart = $cartManagerService->getCart($cartId);
        $newcart = $cartManagerService->deepClone($cart);
        $lightCartProvider->setCartCookie($newcart->getCartId());
        return $this->redirectToRoute('cart', ['id' => $newcart->getCartId()]);
    }

    #[Route(path: '/remove-save-cart/{id}/{itemId}', name: 'remove_save_cart', defaults: ['itemId' => 'all'])]
    public function removeSaveCart($itemId, SavedCart $savedCart, SavedCartRepository $savedCartRepository, EntityManagerInterface $entityManager, CartItemRepository $cartItemRepository): Response
    {
        try {
            if ($savedCart instanceof SavedCart && $savedCart->getUser() === $this->getUser()) {
                if (count($savedCart->getCart()->getCartItems()) > 1 && $itemId !== 'all') {
                    $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy(['itemId' => $itemId]);
                    $cartItemRepository->remove($cartItem, true);
                } else {
                    $savedCartRepository->remove($savedCart, true);
                }
                $this->addFlash('success', 'Saved cart removed successfully');
            } else {
                $this->addFlash('danger', 'You are not allowed to remove this saved cart');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'An error occurred: ' . $e->getMessage());
        }

        return $this->redirectToRoute('saved_card');
    }

    #[Route(path: '/remove-save-design/{id}', name: 'remove_save_design')]
    public function removeSaveDesign(SavedDesign $savedDesign, SavedDesignRepository $savedDesignRepository): Response
    {
        try {
            if ($savedDesign instanceof SavedDesign && $savedDesign->getUser() === $this->getUser()) {
                $savedDesignRepository->remove($savedDesign, true);
                $this->addFlash('success', 'Saved design removed successfully');
            } else {
                $this->addFlash('danger', 'You are not allowed to remove this saved cart');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'An error occurred: ' . $e->getMessage());
        }

        return $this->redirectToRoute('save_design');
    }

    #[Route(path: '/apply-free-shipping', name: 'apply_free_shipping', methods: ['POST'])]
    public function applyFreeShipping(Request $request, CartManagerService $cartManagerService, EntityManagerInterface $entityManager, ShippingChartHelper $shippingChartHelper, OrderSampleHelper $orderSampleHelper): Response
    {
        $cartId = $request->get('cartId');

        $cart = $cartManagerService->getCart($cartId);
        $freeShipping = $this->getFreeShipping($entityManager, $shippingChartHelper, $cart->getTotalQuantity());

        foreach ($cart->getCartItems() as $item) {
            if ($item->getDataKey('isSample')) {
                $freeShipping = $orderSampleHelper->getFreeShipping();
                if (is_array($freeShipping)) {
                    $freeShipping['amount'] = 0;
                }
            } else {
                $freeShipping = $this->getFreeShipping($entityManager, $shippingChartHelper, $cart->getTotalQuantity());
            }
            $item->setShipping($freeShipping);
            $item->setDataKey('shipping', $freeShipping);
            $entityManager->persist($item);
        }

        $cart->setDataKey('shipping', $freeShipping);
        $cart->setDataKey('deliveryDate', $freeShipping);

        $cart->setTotalShipping(0);
        $entityManager->persist($cart);
        $entityManager->flush();

        $this->addFlash('success', 'Free shipping applied');
        return $this->redirectToRoute('cart', ['id' => $cart->getCartId()]);
    }

    private function getFreeShipping(EntityManagerInterface $entityManager, ShippingChartHelper $shippingChartHelper, int $quantity, array $shipping = []): array
    {
        $productType = $entityManager->getRepository(ProductType::class)->findOneBy(['slug' => 'yard-sign']);
        $shippings = !empty($shipping) ? $shipping : $productType->getShipping();
        $shippingChartHelper->setFreeShippingEnabled(!empty($shipping) ? false : true);
        $shippingChart = $shippingChartHelper->build($shippings);
        $shippingChart = $shippingChartHelper->getShippingByQuantity($quantity, $shippingChart);

        $freeShipping = array_filter($shippingChart, function ($shipping) {
            return $shipping['discount'] === 0 && $shipping['free'] === true;
        });

        if (!empty($freeShipping)) {
            return end($freeShipping);
        }

        return end($shippingChart);
    }

    #[Route(path: '/new', name: 'new', methods: ['GET'])]
    public function newCart(CartManagerService $cartManagerService): Response
    {
        $newCart = $cartManagerService->createCart();
        return $this->redirectToRoute('cart', ['id' => $newCart->getCartId()]);
    }


    #[Route('/cart/download-quote/{cartId}', name: 'cart_download_quote')]
    public function downloadQuote(
        string $cartId,
        CartManagerService $cartManagerService,
        StoreInfoService $storeInfoService,
        TwigEnvironment $twig
    ): Response {
        $cart = $cartManagerService->getCart($cartId);
        $storeInfo = $storeInfoService->storeInfo();

        if ($storeInfo['isPromoStore']) {
            $primary = '#25549b';
        } else {
            $primary = '#6f4c9e';
        }

        $html = $twig->render('cart/pdf_quote.html.twig', [
            'cart' => $cart,
            'items' => $cart->getCartItems(),
            'quoteDate' => (new \DateTime())->format('d M Y h:i A'),
            'storeInfo' => $storeInfo,
            'primary' => $primary,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setHttpContext($context);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Quote.pdf"',
            ]
        );
    }

    #[Route(path: '/download-quote', name: 'download_quote', methods: ['POST'])]
    public function editorDownloadQuote(Request $request, PdfService $pdfService): Response
    {
        $editorData = $request->get('editor', []);

        if (!$editorData) {
            return $this->json(['error' => 'Invalid editor data'], 400);
        }

        $pdfContent = $pdfService->generateQuotePdf($editorData);

        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="quote.pdf"',
            ]
        );
    }
}
