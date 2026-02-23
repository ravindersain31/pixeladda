<?php

namespace App\Controller\Web;

use App\Entity\Admin\Coupon;
use App\Entity\AppUser;
use App\Entity\User;
use App\Enum\CouponTypeEnum;
use App\Service\StoreInfoService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReferralCouponController extends AbstractController
{
    
    #[Route('/rc/{code}', name: 'apply_referral_code')]
    public function referralCode(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session
    ) {
        $code = $request->get('code');
        $coupon = $em->getRepository(Coupon::class)->findOneBy([
            'code' => $code,
            'couponType' => CouponTypeEnum::REFERRAL, 
        ]);

        if (!$coupon) {
            $this->addFlash('danger', 'Invalid referral code.');
            return $this->redirectToRoute('homepage');
        }
     
        $now = new \DateTimeImmutable();

        if ($coupon->getStartDate() && $coupon->getStartDate() > $now) {
            $this->addFlash('danger', 'This referral code is not active yet.');
            return $this->redirectToRoute('homepage');
        }

        if ($coupon->getEndDate() && $coupon->getEndDate() < $now) {
            $this->addFlash('danger', 'This referral code has expired.');
            return $this->redirectToRoute('homepage');
        }


        if ($session->has('referralCode')) {
            $this->addFlash('danger', 'Referral code already applied.');
            return $this->redirectToRoute('homepage');
        }

        
        $clientIp = $request->getClientIp();
        if ($request->headers->has('X-Forwarded-For')) {
            $clientIp = trim(explode(',', $request->headers->get('X-Forwarded-For'))[0]);
        }

        if ($coupon->getDeviceIp() === $clientIp) {
            $this->addFlash('danger', 'This coupon has already been redeemed.');
            return $this->redirectToRoute('homepage');
        }

        $session->set('referralCode', $code);
        $this->addFlash('success', 'Referral code applied successfully.');
        return $this->redirectToRoute('homepage');
    }


    #[Route('/customer-referral', name: 'api_customer_referral', methods: ['POST'])]
    public function customerReferral(
        Request $request,
        EntityManagerInterface $em,
        StoreInfoService $storeInfoService,
        UserService $userService
    ) {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        if (!$email) {
            return $this->json([
                'status' => false,
                'message' => 'Email is required.'
            ]);
        }

        $now = new \DateTimeImmutable();
        
        $user = $this->getUser() ?? $userService->getUserFromAddress(['email' => $email]);
        $userExists = (bool) $user;

        $existingCoupon = $em->getRepository(Coupon::class)->findOneBy(
            ['couponType' => CouponTypeEnum::REFERRAL, 'user' => $user],
            ['id' => 'DESC']
        );

        $createNewCoupon = false;

        if (!$existingCoupon) {
            $createNewCoupon = true; 
        } elseif ($existingCoupon->getEndDate() < $now) {
            $createNewCoupon = true; 
        }

        if ($createNewCoupon) {
            $coupon = new Coupon();
            $coupon->setUser($user); 
            $coupon->setCode($this->generateUniqueCouponCode($em));
            $coupon->setCouponName('5% Referral discount');
            $coupon->setDiscount(5);
            $coupon->setType(CouponTypeEnum::PERCENTAGE->value);
            $coupon->setCouponType(CouponTypeEnum::REFERRAL);
            $coupon->setIsEnabled(true);
            $coupon->setUsesTotal(100);
            $coupon->setStore($storeInfoService->getStore());
            $coupon->setStartDate($now);
            $coupon->setEndDate($now->modify('+30 days'));

            $clientIp = $request->headers->get('X-Forwarded-For')
                ? explode(',', $request->headers->get('X-Forwarded-For'))[0]
                : $request->getClientIp();
            $coupon->setDeviceIp($clientIp);

            $em->persist($coupon);
            $em->flush();
        } else {
            $coupon = $existingCoupon;
        }

        $response = [
            'status' => true,
            'referralUrl' => $this->generateUrl('apply_referral_code', ['code' => $coupon->getCode()],UrlGeneratorInterface::ABSOLUTE_URL),
            'userExists' => $userExists
        ];

        if (!$userExists) {
            $response['message'] = 'Create an account to receive and use your rewards!';
        }

        return $this->json($response);
    }

    private function generateUniqueCouponCode(EntityManagerInterface $em): string
    {
        do {
            $code = substr(str_replace(['-', '_'], '', \Ramsey\Uuid\Uuid::uuid4()->toString()), 0, 10);
        } while ($em->getRepository(Coupon::class)->findOneBy(['code' => $code]));
        return $code;
    }
}
