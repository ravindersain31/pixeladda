<?php

namespace App\Controller\Web;

use App\Constant\Faqs;
use App\Constant\MetaData\Page;
use App\Entity\BulkOrder;
use App\Entity\CommunityUploads;
use App\Entity\Subscriber;
use App\Entity\CustomerPhotos;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Form\BulkOrderType;
use App\Form\Page\ContactUsType;
use App\Form\Page\RequestCallBackType;
use App\Form\UploadPhotosFooterType;
use App\Form\Page\ViewProofType;
use App\Helper\ProductConfigHelper;
use App\Helper\VichS3Helper;
use App\Repository\CustomerPhotosRepository;
use App\Repository\ProductRepository;
use App\Service\BulkOrderService;
use App\Service\CartManagerService;
use App\Service\ContactUsService;
use App\Service\RecaptchaManager;
use App\Service\RequestCallBackService;
use App\Service\StoreInfoService;
use App\Service\SubscriberService;
use App\Repository\CategoryRepository;
use App\Trait\StoreTrait;
use App\Twig\ConfigProvider;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Enum\ProductEnum;
use App\Form\ProductTypePricingFormType;
use App\Helper\PriceChartHelper;
use App\Repository\FaqRepository;
use App\Repository\ProductTypeRepository;
use App\Form\ShopFilterType;

class PageController extends AbstractController
{
    use StoreTrait;

    #[Route('/about-us', name: 'about_us')]
    public function aboutUs(Request $request, EntityManagerInterface $entityManager, StoreInfoService $storeInfoService): Response
    {
        $photos = new CommunityUploads;
        $form = $this->createForm(UploadPhotosFooterType::class, $photos);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photos->setIsEnabled(false);
            $photos->setStore($entityManager->getReference(Store::class, $this->store['id']));
            $entityManager->persist($photos);
            $entityManager->flush();
            $this->addFlash('success', 'Customer photo uploaded successfully.');
            $this->redirectToRoute('about_us');
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/about-us.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/contact-us', name: 'contact_us')]
    public function contactUs(Request $request, Session $session, RateLimiterFactory $recaptchaFailuresLimiter, RecaptchaManager $recaptchaManager, EntityManagerInterface $entityManager, ContactUsService $contactUsService, StoreInfoService $storeInfoService): Response
    {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $showRecaptcha = $recaptchaManager->shouldShowRecaptcha($request, $recaptchaFailuresLimiter);
        $form = $this->createForm(ContactUsType::class, null, [
            'showRecaptcha' => $showRecaptcha,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');
            $data = $form->getData();

            $contactUsService->contactUs(
                email: $data['email'],
                fullName: $data['name'],
                phone: $data['telephone'],
                comment: $data['comment'],
                store: $entityManager->getReference(Store::class, 1),
            );

            // $email = (new TemplatedEmail());
            // $email->from(new Address(StoreConfigEnum::SALES_EMAIL, StoreConfigEnum::STORE_NAME));
            // $email->subject("Contact request from " . $contactUs->getName());
            // $email->to(StoreConfigEnum::ADMIN_EMAIL);
            // $email->htmlTemplate('emails/contact-us.html.twig')->context([
            //     'contact' => $contactUs,
            // ]);
            // $this->mailer->send($email);

            $this->addFlash('success', 'Thank you for your enquiry. We will be in touch shortly.');
            return $this->redirectToRoute('contact_us');
        } else {
            if ($form->isSubmitted() && !$form->isValid()) {
                $limiter->consume();
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/contact-us.html.twig', [
            'form' => $form->createView(),
            'metaData' => $metaData
        ]);
    }

    #[Route('/bulk-order-enquiry', name: 'bulk_order_enquiry')]
    public function bulkOrder(Request $request, StoreInfoService $storeInfoService, Session $session, RateLimiterFactory $recaptchaFailuresLimiter, RecaptchaManager $recaptchaManager, BulkOrderService $bulkOrderService, EntityManagerInterface $entityManager): Response
    {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $showRecaptcha = $recaptchaManager->shouldShowRecaptcha($request, $recaptchaFailuresLimiter);
        $host = $storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $entityManager->getRepository(StoreDomain::class)
            ->findOneBy(['domain' => $host]);
        $store = $storeDomain?->getStore() ?? $entityManager->getReference(Store::class, 1);
        $form = $this->createForm(BulkOrderType::class, null, [
            'showRecaptcha' => $showRecaptcha,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');
            /** @var BulkOrder $bulkOrder */
            $bulkOrder = $form->getData();

            // Optional: call your service if it handles saving
            $bulkOrderService->createBulkOrder(
                email: $bulkOrder->getEmail(),
                firstName: $bulkOrder->getFirstName(),
                lastName: $bulkOrder->getLastName(),
                company: $bulkOrder->getCompany(),
                quantity: $bulkOrder->getQuantity(),
                budget: $bulkOrder->getBudget(),
                deliveryDate: $bulkOrder->getDeliveryDate(),
                phoneNumber: $bulkOrder->getPhoneNumber(),
                productInInterested: $bulkOrder->getProductInInterested(),
                comment: $bulkOrder->getComment(),
                status: $bulkOrder->getStatus(),
                store: $store,
                storeDomain: $storeDomain,
            );

            $this->addFlash('success', 'Thank you for your enquiry. We will be in touch shortly.');
            return $this->redirectToRoute('bulk_order_enquiry');
        } else {
            if ($form->isSubmitted() && !$form->isValid()) {
                $limiter->consume();
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/bulk-order.html.twig', [
            'form' => $form->createView(),
            'metaData' => $metaData
        ]);
    }
    
    #[Route('/request-call-back', name: 'request_call_back')]
    public function requestCallBack(Request $request, Session $session, RateLimiterFactory $recaptchaFailuresLimiter, RecaptchaManager $recaptchaManager, EntityManagerInterface $entityManager, RequestCallBackService $requestCallBackService, StoreInfoService $storeInfoService): Response
    {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $showRecaptcha = $recaptchaManager->shouldShowRecaptcha($request, $recaptchaFailuresLimiter);
        $form = $this->createForm(RequestCallBackType::class, null, [
            'showRecaptcha' => $showRecaptcha,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');
            $data = $form->getData();

            $requestCallBackService->requestCallBack(
                fullName: $data['name'],
                phone: $data['telephone'],
                comment: $data['comment'],
                store: $entityManager->getReference(Store::class, 1),
            );

            $this->addFlash('success', 'Thank you for your request. One of our sales representatives will get in touch with you shortly.');
            return $this->redirectToRoute('contact_us');
        } else {
            if ($form->isSubmitted() && !$form->isValid()) {
                $limiter->consume();
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/request-call-back.html.twig', [
            'form' => $form->createView(),
            'metaData' => $metaData
        ]);
    }

    #[Route('/faqs', name: 'faqs')]
    public function faqs(Faqs $faqs, Request $request, StoreInfoService $storeInfoService): Response
    {
        $faqs = $faqs->getFaqs();
        $faqsTitle = Faqs::FAQS_TITLE;

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/faqs.html.twig', [
            'faqs' => $faqs,
            'faqsTitle' => $faqsTitle,
            'metaData' => $metaData
        ]);
    }

    #[Route('/faqs-new', name: 'faqs_new')]
    public function faqsNew(Faqs $faqs, Request $request, StoreInfoService $storeInfoService, FaqRepository $faqRepository): Response
    {
        $faqs = $faqRepository->getFaqsGroupedByType();

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/faqs_new.html.twig', [
            'faqs' => $faqs,
            'metaData' => $metaData
        ]);
    }

    #[Route('/pricing', name: 'pricing')]
    public function pricing(
        StoreInfoService $storeInfoService, 
        ProductTypeRepository $productTypeRepository, 
        PriceChartHelper $priceChartHelper,
        RateLimiterFactory $recaptchaFailuresLimiter,
        RecaptchaManager $recaptchaManager,
        Session $session,
        Request $request
    ): Response
    {
        $user = $this->getUser();
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta('pricing', $storeName);
        $productType = $productTypeRepository->findBySlug('yard-sign');
        
        if (!$productType) {
            return $this->redirectToRoute('homepage');
        }
        
        $pricing = $productType->getPricing();
        $pricing = $priceChartHelper->getHostBasedPrice($pricing, $productType, $user);
        $framePricing = $productType->getFramePricing();
        $framePricing = $priceChartHelper->getHostBasedPrice($framePricing, $productType, $user);

        $form = $this->createForm(ProductTypePricingFormType::class, null, [
            'default_slug' => 'yard-sign'
        ]);

        $sortedPricing = $priceChartHelper->getSortedPricingBySlug("yard-sign", $pricing);


        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $showRecaptcha = $recaptchaManager->shouldShowRecaptcha($request, $recaptchaFailuresLimiter);
       
        $bulkform = $this->createForm(BulkOrderType::class, null, [
            'showRecaptcha' => $showRecaptcha,
        ]);
        $bulkform->handleRequest($request);
        if ($bulkform->isSubmitted() && $bulkform->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');
            return $this->redirectToRoute('pricing');
        } else {
            if ($bulkform->isSubmitted() && !$bulkform->isValid()) {
                $limiter->consume();
            }
        }

        return $this->render('pages/pricing.html.twig', [
            'metaData' => $metaData,
            'pricing' => $sortedPricing,
            'framePricing' => $framePricing,
            'productType' => $productType,
            'form' => $form->createView(),
            'bulkform' => $bulkform->createView(),
        ]);
    }

    #[Route('/return-policy', name: 'return_policy')]
    public function returnPolicy(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/return-policy.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/privacy-policy', name: 'privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('pages/privacy-policy.html.twig');
    }

    #[Route('/terms-and-conditions', name: 'terms_and_conditions')]
    public function termsAndCondition(): Response
    {
        return $this->render('pages/terms-and-conditions.html.twig');
    }

    #[Route('/customer-reviews', name: 'customer_feedback')]
    public function customerFeedback(StoreInfoService $storeInfoService): Response
    {

        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta("customer_reviews", $storeName);

        return $this->render('pages/customer-feedback.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/bulk-order-program', name: 'bulk_order_program')]
    public function bulkOrderProgram(Faqs $faqs, Request $request, StoreInfoService $storeInfoService): Response
    {
        $faqs = $faqs->getFaqs();
        $faqsTitle = Faqs::FAQS_TITLE;

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/bulk-order-program.html.twig', [
            'faqs' => $faqs,
            'faqsTitle' => $faqsTitle,
            'metaData' => $metaData
        ]);
    }


    #[Route('/healthcare-workers', name: 'healthcare_workers')]
    public function healthcareWorkers(Faqs $faqs, Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        $faqs = $faqs->getHealthcareWorkersAndFirstResponderFaqs();
        return $this->render('pages/healthcare_workers.html.twig', ['faqs' => $faqs, 'metaData' => $metaData]);
    }

    #[Route('/first-responder', name: 'first_responder')]
    public function firstResponder(Faqs $faqs, Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        $faqs = $faqs->getHealthcareWorkersAndFirstResponderFaqs();
        return $this->render('pages/first-responder.html.twig', ['faqs' => $faqs, 'metaData' => $metaData]);
    }

    #[Route('/order-protection', name: 'order_protection')]
    public function orderProtection(): Response
    {
        return $this->render('pages/order-protection.html.twig');
    }

    #[Route('/shipping-information', name: 'shipping_information')]
    public function shippingInformation(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/shipping-information.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/purchase-order', name: 'purchase_order')]
    public function purchaseOrders(): Response
    {
        return $this->render('pages/purchase-order.html.twig');
    }

    #[Route('/download-catalog', name: 'download_catalog')]
    public function downloadCatalog(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/download-catalog.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/scholarship', name: 'scholarship')]
    public function scholarship(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/scholarship.html.twig', [
            'metaData' => $metaData
        ]);
    }
    #[Route('/member-organization-discount', name: 'organization_members')]
    public function organizationMembers(Faqs $faqs, StoreInfoService $storeInfoService): Response
    {
        $faqs = $faqs->getMembershipFaqs();

        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta('member_organization_discount', $storeName);

        return $this->render('pages/membership_discount.html.twig',['faqs' => $faqs, 'metaData' => $metaData]);
    }

    #[Route('/teacher-discount', name: 'teacher_discount')]
    public function teacherDiscount(Faqs $faqs, Request $request, StoreInfoService $storeInfoService): Response
    {
        $faqs = $faqs->getTeacherDiscountFaqs();

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/teacher_discount.html.twig', ['faqs' => $faqs, 'metaData' => $metaData]);
    }
    #[Route('/military-veterans-discount', name: 'military_veterans')]
    public function militaryVeterans(Faqs $faqs, StoreInfoService $storeInfoService): Response
    {
        $faqs = $faqs->getMilitaryVeteransFaqs();

        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta('military_veterans_discount', $storeName);

        return $this->render('pages/military_veterans.html.twig', ['faqs' => $faqs, 'metaData' => $metaData]);
    }

    #[Route('/shop-now', name: 'shop_now')]
    public function shopNow(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/shop-now.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/subscribe-and-save-today', name: 'subscribe_and_Save')]
    public function subscribeAndSaveToday(): Response
    {
        return $this->render('pages/save_and_subscribe.html.twig');
    }

    #[Route('/unsubscribe', name: 'unsubscribe_user')]
    public function unsubscribeUser(): Response
    {
        return $this->render('pages/unsubscribe_user.html.twig');
    }

    #[Route('/repeat-order', name: 'repeat_order')]
    public function repeatOrder(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/repeat_order.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/site-map', name: 'site_map')]
    public function siteMap(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->getCategoryHasProductsSelective(
            $this->store['id'],
            displayInMenu: null,
            showAll: true
        );

        $categorized = [];

        foreach ($categories as $cat) {
            if ($cat['parentSlug'] === null) {
                $categorized[$cat['slug']] = [
                    'parent' => $cat,
                    'children' => []
                ];
            }
        }

        foreach ($categories as $cat) {
            if ($cat['parentSlug'] !== null && isset($categorized[$cat['parentSlug']])) {
                $categorized[$cat['parentSlug']]['children'][] = $cat;
            }
        }

        $categorizedArray = array_values($categorized);

        $total = count($categorizedArray);
        $chunk = ceil($total / 2);

        $categoryCol1 = array_slice($categorizedArray, 0, $chunk);
        $categoryCol2 = array_slice($categorizedArray, $chunk);

        return $this->render('pages/sitemap.html.twig', [
            'categoryCol1' => $categoryCol1,
            'categoryCol2' => $categoryCol2
        ]);
    }

    #[Route('/ysp-rewards', name: 'ysp_rewards')]
    public function yspRewards(): Response
    {
        $rewardsFaqs = Faqs::REWARDS_FAQS;
        return $this->render('pages/ysp_rewards.html.twig', [
            'rewardsFaqs' => $rewardsFaqs
        ]);
    }

    #[Route('/track-order', name: 'track_order')]
    public function trackOrder(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/track_order.html.twig', [
            'metaData' => $metaData
        ]);
    }

    #[Route('/track-order-details/{oid}', name: 'track_order_details')]
    public function trackOrderDetails(string $oid, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderId' => $oid]);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('pages/track_order_details.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/customer-photos', name: 'customer_photos')]
    public function customerPhotos(Request $request, CustomerPhotosRepository $customerPhotos, PaginatorInterface $paginator, EntityManagerInterface $entityManager, ConfigProvider $configProvider, StoreInfoService $storeInfoService): Response
    {

        $store = $entityManager->getReference(Store::class, $this->store['id']);

        $photos = $customerPhotos->findBy(['isEnabled' => true, 'store' => $store], ['createdAt' => 'DESC']);
        $page = $request->get('page', 1);
        $customerPhotos = $paginator->paginate($photos, $page, 24);

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/customer_photos.html.twig', [
            'customerPhotos' => $customerPhotos,
            'metaData' => $metaData
        ]);
    }

    #[Route("/order-sample-old", name: "order_sample_old")]
    public function orderSampleOld(Request $request, ProductRepository $productRepository, ProductConfigHelper $configHelper, CartManagerService $cartManagerService): Response
    {
        $store = $this->getStore();
        $product = $productRepository->findOneBy(['store' => $store->id, 'sku' => 'SAMPLE']);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $variant = $request->get('variant', '6x18');
        $productVariant = $productRepository->isVariantExists($product, $variant);
        if (!$productVariant) {
            throw $this->createNotFoundException('Product variant not found');
        }

        $productConfig = $configHelper->makeProductConfig($product, []);

        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');
        $editData = null;
        if ($cartId && $itemId) {
            $cart = $cartManagerService->getCart($cartId);
            $editData = $cartManagerService->validateEditItem($cart, $itemId);
            if ($editData instanceof RedirectResponse) {
                return $editData;
            }
            $editData = $cartManagerService->getCartSerialized($editData['cart'], $itemId);
        }

        return $this->render('pages/order_sample_old.html.twig', [
            'product' => $productConfig,
            'editData' => $editData,
        ]);
    }
    #[Route(path: '/order-sample', name: 'order_sample', methods: ['GET'])]
    public function orderSample(Request $request, ProductRepository $productRepository, ProductConfigHelper $configHelper, EntityManagerInterface $entityManager, SerializerInterface $serializer, CartManagerService $cartManagerService, VichS3Helper $vichS3Helper, Faqs $faqs, StoreInfoService $storeInfoService): Response
    {
        $store = $this->getStore();

        $product = $productRepository->findOneBy(['store' => $store->id, 'sku' => 'SAMPLE']);

        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');
        $cart = $cartManagerService->getCart($cartId);

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $productConfig = $configHelper->makeSampleProductConfig($product, $editData);

        $variant = $request->get('variant', null);

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $cartOverview = $cartManagerService->getCartOverview($cart);

        $isDefaultVariantExists = array_filter($productConfig['variants'], function ($v) use ($variant) {
            return $v['name'] === $variant;
        });
        $fallbackVariant = $variant;
        if (count($isDefaultVariantExists) <= 0) {
            $fallbackVariant = count($productConfig['variants']) > 0 ?? $productConfig['variants'][0]['name'];
            if (!$variant) {
                $variant = $fallbackVariant;
            }
        }

        $cartData = null;
        if (is_array($editData)) {
            $itemId = $request->get('itemId');
            $cartData = $cartManagerService->getCartSerialized($editData['cart'], $itemId);
            $itemToEdit = reset($cartData['items']);
            if (is_array($itemToEdit) && isset($itemToEdit['data'])) {
                $variant = $itemToEdit['data']['name'] ?? $variant;
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/order_sample.html.twig', [
            'product' => $productConfig,
            'editData' => $editData,
            'cartOverview' => $cartOverview,
            'cart' => $cartData,
            'initialData' => [
                'variant' => $variant,
                'quantity' => intval($request->get('qty', 0)),
            ],
            'links' => [
                'add_to_cart' => $this->generateUrl('add_to_cart', [], UrlGeneratorInterface::NETWORK_PATH),
            ],
            'metaData' => $metaData
        ]);
    }

    #[Route("/order-wire-stake-old", name: "order_wire_stake_old")]
    public function orderWireStakes(Request $request, ProductRepository $productRepository, ProductConfigHelper $configHelper, CartManagerService $cartManagerService): Response
    {
        $store = $this->getStore();
        $product = $productRepository->findOneBy(['store' => $store->id, 'sku' => 'WIRE-STAKE']);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $productConfig = $configHelper->makeProductConfig($product, [], pricing: false);

        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');
        $editData = null;
        if($cartId && $itemId) {
            $cart = $cartManagerService->getCart($cartId);
            $editData = $cartManagerService->validateEditItem($cart, $itemId);
            if ($editData instanceof RedirectResponse) {
                return $editData;
            }
            $editData = $cartManagerService->getCartSerialized($editData['cart'], $itemId);
        }

        return $this->render('pages/order-wire-stake-old.html.twig', [
            'product' => $productConfig,
            'editData' => $editData,
        ]);
    }

    #[Route(path: '/order-wire-stake', name: 'order_wire_stake', methods: ['GET'])]
    public function orderWireStake(Request $request, ProductRepository $productRepository, ProductConfigHelper $configHelper, EntityManagerInterface $entityManager, SerializerInterface $serializer, CartManagerService $cartManagerService, VichS3Helper $vichS3Helper, Faqs $faqs, StoreInfoService $storeInfoService): Response
    {
        $store = $this->getStore();

        $product = $productRepository->findOneBy(['store' => $store->id, 'sku' => 'WIRE-STAKE']);

        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');
        $cart = $cartManagerService->getCart($cartId);

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $productConfig = $configHelper->makeWireStakeConfig($product, $editData);

        $variant = $request->get('variant', null);

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $cartOverview = $cartManagerService->getCartOverview($cart);

        $isDefaultVariantExists = array_filter($productConfig['variants'], function ($v) use ($variant) {
            return $v['name'] === $variant;
        });
        $fallbackVariant = $variant;
        if (count($isDefaultVariantExists) <= 0) {
            $fallbackVariant = count($productConfig['variants']) > 0 ?? $productConfig['variants'][0]['name'];
            if (!$variant) {
                $variant = $fallbackVariant;
            }
        }

        $cartData = null;
        if (is_array($editData)) {
            $itemId = $request->get('itemId');
            $cartData = $cartManagerService->getCartSerialized($editData['cart'], $itemId);
            $itemToEdit = reset($cartData['items']);
            if (is_array($itemToEdit) && isset($itemToEdit['data'])) {
                $variant = $itemToEdit['data']['name'] ?? $variant;
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/order-wire-stake.html.twig', [
            'product' => $productConfig,
            'editData' => $editData,
            'cartOverview' => $cartOverview,
            'cart' => $cartData,
            'initialData' => [
                'variant' => $variant,
                'quantity' => intval($request->get('qty', 0)),
            ],
            'links' => [
                'add_to_cart' => $this->generateUrl('add_to_cart', [], UrlGeneratorInterface::NETWORK_PATH),
            ],
            'metaData' => $metaData
        ]);
    }

    #[Route(path: '/order-blank-sign', name: 'order_blank_sign', methods: ['GET'])]
    public function orderBlankSigns(Request $request, ProductRepository $productRepository, ProductConfigHelper $configHelper, CartManagerService $cartManagerService, StoreInfoService $storeInfoService): Response
    {
        $store = $this->getStore();

        $product = $productRepository->findOneBy(['store' => $store->id, 'sku' => ProductEnum::BLANK_SIGN->value]);

        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found');
        }

        $cartId = $request->get('cartId');
        $itemId = $request->get('itemId');
        $cart = $cartManagerService->getCart($cartId);

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $productConfig = $configHelper->makeProductConfig($product, $editData);
        $variant = $request->get('variant', null);

        $editData = $cartManagerService->validateEditItem($cart, $itemId);
        if ($editData instanceof RedirectResponse) {
            return $editData;
        }

        $cartOverview = $cartManagerService->getCartOverview($cart);

        $isDefaultVariantExists = array_filter($productConfig['variants'], function ($v) use ($variant) {
            return $v['name'] === $variant;
        });
        $fallbackVariant = $variant;
        if (count($isDefaultVariantExists) <= 0) {
            $fallbackVariant = count($productConfig['variants']) > 0 ?? $productConfig['variants'][0]['name'];
            if (!$variant) {
                $variant = $fallbackVariant;
            }
        }

        $cartData = null;
        if (is_array($editData)) {
            $itemId = $request->get('itemId');
            $cartData = $cartManagerService->getCartSerialized($editData['cart'], $itemId);
            $itemToEdit = reset($cartData['items']);
            if (is_array($itemToEdit) && isset($itemToEdit['data'])) {
                $variant = $itemToEdit['data']['name'] ?? $variant;
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/order-blank-sign.html.twig', [
            'product' => $productConfig,
            'editData' => $editData,
            'cartOverview' => $cartOverview,
            'cart' => $cartData,
            'initialData' => [
                'variant' => $variant,
                'quantity' => intval($request->get('qty', 0)),
            ],
            'links' => [
                'add_to_cart' => $this->generateUrl('add_to_cart', [], UrlGeneratorInterface::NETWORK_PATH),
            ],
            'metaData' => $metaData
        ]);
    }

    #[Route('/view-proof', name: 'view_proof')]
    public function viewProof(Request $request, Session $session, RateLimiterFactory $recaptchaFailuresLimiter, RecaptchaManager $recaptchaManager, StoreInfoService $storeInfoService): Response
    {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);
        $showRecaptcha = $recaptchaManager->shouldShowRecaptcha($request, $recaptchaFailuresLimiter);
        $form = $this->createForm(ViewProofType::class, null, [
            'showRecaptcha' => $showRecaptcha,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');
            return $this->redirectToRoute('view_proof');
        } else {
            if ($form->isSubmitted() && !$form->isValid()) {
                $limiter->consume();
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/view-proof.html.twig', [
            'form' => $form->createView(),
            'metaData' => $metaData
        ]);
    }

    #[Route('/how-to-order', name: 'how_to_order')]
    public function howToOrder(Request $request, StoreInfoService $storeInfoService): Response
    {
        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('pages/how-to-order.html.twig', [
            'metaData' => $metaData
        ]);
    }

   #[Route('/new-arrivals', name: 'new_arrivals')]
    public function newArrivals(
        Request $request,
        StoreInfoService $storeInfoService,
        CategoryRepository $categoryRepository
    ): Response {
        $selectedCategory = is_array($request->get('c'))
            ? reset($request->get('c'))
            : $request->get('c');

        $categoriesHasProducts = $categoryRepository
            ->getCategoryHasProductsSelective(
                $this->store['id'],
                displayInMenu: null,
                showAll: false,
                orderBy: 'latest'
            );

        $categoriesHasProducts = array_slice($categoriesHasProducts, 0, 15);
        $filterForm = $this->createForm(
            ShopFilterType::class,
            $categoriesHasProducts,
            [
                'method' => 'GET',
                'validation_groups' => false
            ]
        );

        $request->query->set('c', $selectedCategory);
        $filterForm->handleRequest($request);

        $categories = $filterForm->get('c')->getData()
            ? [$filterForm->get('c')->getData()]
            : [];

        return $this->render('pages/new_arrivals.html.twig', [
            'filterForm' => $filterForm->createView(),
            'chooseCategories' => $categories,
            'metaData' => Page::getMeta(
                $request->attributes->get('_route'),
                $storeInfoService->storeInfo()['storeName']
            ),
        ]);
    }
}
