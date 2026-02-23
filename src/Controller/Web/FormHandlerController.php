<?php

namespace App\Controller\Web;

use App\Entity\CommunityUploads;
use App\Entity\Order;
use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Enum\StoreConfigEnum;
use App\Form\ExclusiveOfferMobileType;
use App\Form\ExclusiveOfferType;
use App\Form\Page\ContactUsType;
use App\Form\Page\RequestCallBackType;
use App\Form\UploadPhotosFooterType;
use App\Service\RequestCallBackService;
use App\Service\ContactUsService;
use App\Form\Page\ViewProofType;
use App\Service\StoreInfoService;
use App\Service\SubscriberService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

class FormHandlerController extends AbstractController
{
    use StoreTrait;

    public function __construct(
        private readonly StoreInfoService $storeInfoService,
    ) {}

    #[Route('/form/contact-us', name: 'form_contact_us')]
    public function contactUs(Request $request, EntityManagerInterface $entityManager, ContactUsService $contactUsService): Response
    {
        $store = $this->storeInfoService->getStore();
        $contactUsForm = $this->createForm(ContactUsType::class);
        $contactUsForm->handleRequest($request);
        if ($contactUsForm->isSubmitted() && $contactUsForm->isValid()) {
            $data = $contactUsForm->getData();

            $contactUsService->contactUs(
                email: $data['email'],
                fullName: $data['name'],
                phone: $data['telephone'],
                comment: $data['comment'],
                store: $entityManager->getReference(Store::class, $store->getId()),
            );

            return $this->json([
                'success' => true,
                'message' => 'Thank you for your enquiry. We will be in touch shortly.',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please fill the required fields.',
            'errors' => array_values($this->getErrorMessages($contactUsForm)),
        ]);
    }

    #[Route('/form/request-call-back', name: 'form_request_call_back')]
    public function requestCallBack(Request $request, EntityManagerInterface $entityManager, RequestCallBackService $RequestCallBackService): Response
    {
        $store = $this->storeInfoService->getStore();
        $requestCallBackForm = $this->createForm(RequestCallBackType::class);
        $requestCallBackForm->handleRequest($request);
        if ($requestCallBackForm->isSubmitted() && $requestCallBackForm->isValid()) {
            $data = $requestCallBackForm->getData();
            $RequestCallBackService->requestCallBack(
                fullName: $data['name'],
                phone: $data['telephone'],
                comment: $data['comment'],
                store: $entityManager->getReference(Store::class, $store->getId()),
            );

            return $this->json([
                'success' => true,
                'message' => 'Thank you for your request. One of our sales representatives will get in touch with you shortly.',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please fill the required fields.',
            'errors' => array_values($this->getErrorMessages($requestCallBackForm)),
        ]);
    }

    #[Route('/form/add-a-photo', name: 'form_add_a_photo')]
    public function addAPhoto(Request $request, EntityManagerInterface $entityManager): Response
    {
        $communityUpload = new CommunityUploads();
        $form = $this->createForm(UploadPhotosFooterType::class, $communityUpload);
        $form->handleRequest($request);
        $store = $this->storeInfoService->getStore();
        if ($form->isSubmitted() && $form->isValid()) {
            if ($store) {
                $store = $entityManager->getReference(Store::class, $store->getId());
                $communityUpload->setStore($store);
            }
            $entityManager->persist($communityUpload);
            $entityManager->flush();
            return $this->json([
                'success' => true,
                'message' => 'Your upload has been successfully submitted.',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please fill the required fields.',
            'errors' => array_values($this->getErrorMessages($form)),
        ]);
    }

    private function getErrorMessages(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->all() as $field) {
            if ($field->getErrors()->count() > 0) {
                $errors[$field->getName()] = $field->getErrors()->current()->getMessage();
            }
        }

        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }

    #[Route('/form/view-proof', name: 'form_view_proof')]
    public function viewProof(Request $request, EntityManagerInterface $entityManager): Response
    {
        $viewProofForm = $this->createForm(ViewProofType::class);
        $viewProofForm->handleRequest($request);

        if ($viewProofForm->isSubmitted() && $viewProofForm->isValid()) {
            $data = $viewProofForm->getData();

            $hasOrderId = !empty($data['orderId']);
            $hasPhone = !empty($data['telephone']);
            $hasEmail = !empty($data['email']);

            if (!$hasOrderId && !$hasPhone && !$hasEmail) {
                return $this->json([
                    'success' => false,
                    'message' => 'Please provide at least one of the following details.',
                    'errors' => array_values($this->getErrorMessages($viewProofForm)),
                ]);
            }

            if ($hasOrderId && !$hasPhone && !$hasEmail) {
                return $this->json([
                    'success' => false,
                    'message' => 'Please provide at least one of the following with Order Number: Email or Phone Number',
                    'errors' => array_values($this->getErrorMessages($viewProofForm)),
                ]);
            }
            $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];
            $order = $entityManager->getRepository(Order::class)->findOrderProofByDetails($data['email'], $data['telephone'], $data['orderId'], $isPromoStore);
            foreach ($order as &$orderData) {
                $orderData['proofUrl'] = $this->generateUrl('order_proof', ['oid' => $orderData['orderId']]);
                $categoriesJson = $orderData['categories'] ?? '';
                $categoriesArray = json_decode($categoriesJson, true);

                if (is_array($categoriesArray) && isset($categoriesArray['categories'])) {
                    foreach ($categoriesArray['categories'] as &$category) {
                        if ($category['slug'] === 'wire-stake') {
                            $categoryUrl = $this->generateUrl('order_wire_stake');
                        } else if ($category['slug'] === 'sample-category') {
                            $categoryUrl = $this->generateUrl('order_sample');
                        } else if ($category['slug'] === 'custom-signs') {
                            $categoryUrl = $this->generateUrl('custom_yard_sign_editor', ['variant' => '24x18']);
                        } else {
                            $categoryUrl = $this->generateUrl('category', ['slug' => $category['slug']]);
                        }
                        $category['url'] = $categoryUrl;
                    }
                    $orderData['categories'] = $categoriesArray['categories'];
                } else {
                    $orderData['categories'] = [];
                }
            }

            if (!$order) {
                return $this->json([
                    'success' => false,
                    'message' => 'No matching order found with the provided details.',
                    'errors' => [],
                ]);
            }

            if (is_array($order) && count($order) > 1) {
                return $this->json([
                    'success' => true,
                    'message' => 'Order found successfully.',
                    'orders' => $order,
                ]);
            } else {
                return $this->json([
                    'success' => true,
                    'message' => 'Order found successfully.',
                    'redirectUrl' => $this->generateUrl('order_proof', ['oid' => $order[0]['orderId']]),
                ]);
            }
        }

        return $this->json([
            'success' => false,
            'message' => 'Form submission is invalid. Please check the provided details.',
            'errors' => array_values($this->getErrorMessages($viewProofForm)),
        ]);
    }

    #[Route('/form/exclusive-offer', name: 'form_exclusive_offer')]
    public function exclusiveOffer(Request $request, StoreInfoService $storeInfoService, EntityManagerInterface $entityManager, SubscriberService $subscriberService, MailerInterface $mailer): Response
    {
        $host = $storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $entityManager->getRepository(StoreDomain::class)
            ->findOneBy(['domain' => $host]);
        $exclusiveOfferForm = $this->createForm(ExclusiveOfferType::class);
        $exclusiveOfferForm->handleRequest($request);
        if ($exclusiveOfferForm->isSubmitted() && $exclusiveOfferForm->isValid()) {
            $data = $exclusiveOfferForm->getData();

            $this->sendExclusiveOfferEmail($data['email'], $mailer);
            $store = is_array($request->get('store')) ? $request->get('store')['id'] : 1;
            $subscriberService->subscribe(
                email: $data['email'],
                type: SubscriberService::ENQUIRY_SAVE_OFFER,
                offers: true,
                store: $entityManager->getReference(Store::class, $store),
                storeDomain: $storeDomain,
            );

            return $this->json([
                'success' => true,
                'message' => 'Thank you for subscribing! Please check your email for updates.',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please fill the required fields.',
            'errors' => array_values($this->getErrorMessages($exclusiveOfferForm)),
        ]);
    }

    #[Route('/form/exclusive-offer-mobile', name: 'form_exclusive_offer_mobile')]
    public function exclusiveOfferMobile(Request $request, EntityManagerInterface $entityManager, SubscriberService $subscriberService, MailerInterface $mailer): Response
    {
        $exclusiveOfferMobileForm = $this->createForm(ExclusiveOfferMobileType::class);
        $exclusiveOfferMobileForm->handleRequest($request);
        if ($exclusiveOfferMobileForm->isSubmitted() && $exclusiveOfferMobileForm->isValid()) {
            $data = $exclusiveOfferMobileForm->getData();

            $this->sendExclusiveOfferEmail($data['email'], $mailer);
            $store = is_array($request->get('store')) ? $request->get('store')['id'] : 1;
            $subscriberService->subscribe(
                email: $data['email'],
                type: SubscriberService::ENQUIRY_SAVE_OFFER,
                offers: true,
                store: $entityManager->getReference(Store::class, $store),
            );

            return $this->json([
                'success' => true,
                'message' => 'Thank you for subscribing! Please check your email for updates.',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please fill the required fields.',
            'errors' => array_values($this->getErrorMessages($exclusiveOfferMobileForm)),
        ]);
    }

    private function sendExclusiveOfferEmail(string $userEmail, MailerInterface $mailer): void
    {
        $storeName = $this->storeInfoService->getStoreName();
        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
        $email->subject("Thank You for Subscribing! Save 10% Today!");
        $email->to($userEmail);
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL,  $storeName));
        $email->htmlTemplate('emails/exclusive_offer.html.twig')->context([
            'show_unsubscribe_link' => true,
            'user_email' => $userEmail
        ]);
        $mailer->send($email);
    }
}
