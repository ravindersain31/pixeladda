<?php

namespace App\Controller\Web;

use App\Constant\MetaData\Page;
use App\Entity\State;
use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Form\DistributorType;
use App\Helper\RecaptchaValidator;
use App\Service\RecaptchaManager;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class DistributorController extends AbstractController
{
    public const MAX_ATTEMPTS = 10;

    #[Route('/distributor', name: 'distributor_form', methods: ['GET', 'POST'])]
    public function distributor(
        Request $request,
        StoreInfoService $storeInfoService,
        Session $session,
        RateLimiterFactory $recaptchaFailuresLimiter,
        RecaptchaManager $recaptchaManager,
        EntityManagerInterface $entityManager,
    ) {
        $ip = getHostByName(getHostName());
        $limiter = $recaptchaFailuresLimiter->create($ip);

        $showRecaptcha = $recaptchaManager->shouldShowRecaptchaV2($request, $recaptchaFailuresLimiter);

        $host = $storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $entityManager->getRepository(StoreDomain::class)
            ->findOneBy(['domain' => $host]);
        $store = $storeDomain?->getStore() ?? $entityManager->getReference(Store::class, 1);

        $form = $this->createForm(DistributorType::class, null, [
            'showRecaptcha' => $showRecaptcha,
        ]);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->reset();
            $session->remove('recaptcha_enabled_at');

            $data = $form->getData(); 
            $data->setStoreDomain($storeDomain);
             $now = new \DateTimeImmutable();
            $data->setCreatedAt($createdAt ?? $now);
            $data->setUpdatedAt($updatedAt ?? $now);
            $entityManager->persist($data);
            $entityManager->flush();

            $this->addFlash('success', 'Thank you for submitting your distributor application form. We look forward to partnering with you!');
            return $this->redirectToRoute('distributor_form');
        } else {
            if ($form->isSubmitted() && !$form->isValid()) {

                if ($session->has('recaptcha_enabled_at')) {
                    $limiter->reset();
                    $session->remove('recaptcha_enabled_at');

                    $limiter->consume();
                } else {
                    $limiter->consume();
                }

                $this->addFlash('danger', 'Application submitted failed. ');
            }
        }

        $routeName = $request->attributes->get('_route');
        $storeName = $storeInfoService->storeInfo()['storeName'];
        $metaData = Page::getMeta($routeName, $storeName);

        return $this->render('distributor/index.html.twig', [
            'form' => $form->createView(),
            'metaData' => $metaData,
        ]);
    }
}
