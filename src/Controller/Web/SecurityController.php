<?php

namespace App\Controller\Web;

use App\Entity\AppUser;
use App\Enum\WholeSellerEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Form\Otp\AuthOtpRequestType;
use App\Form\Otp\AuthOtpVerifyType;
use App\Repository\UserRepository;
use App\Security\AppUserAuthenticator;
use App\Service\AuthOtpService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private AuthOtpService $authOtpService
    ) {}

    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('my_account');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'loginTitle' => 'User Login',
            'signupRoute' => WholeSellerEnum::CREATE_ACCOUNT_ROUTE->value,
            'createAccount' => 'Create Account',
            'wholeSellerShow'=> false,
        ]);
    }

    #[Route(path: '/whole-seller-login', name: 'whole_seller_login')]
    public function wholeSellerLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('my_account');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'loginTitle' => 'Wholesaler Login',
            'signupRoute' => WholeSellerEnum::WHOLE_SELLER_CREATE_ACCOUNT_ROUTE->value,
            'createAccount' => 'Wholesaler Sign Up',
            'wholeSellerShow'=> true,
        ]);
    }

    #[Route('/login-via-otp', name: 'login_via_otp')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(AuthOtpRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->userRepository->findOneBy(['username' => $email]);

            if (!$user) {
                $this->addFlash('danger', 'User not found.');
            } else {
                $this->authOtpService->generateAndSendOtp($user);
                $this->addFlash('success', 'One Time Password sent to your email.');
                 return $this->redirectToRoute('login_otp_verify', ['id' => $user->getId()]);
            }
        }

        return $this->render('otp/login_otp_request.html.twig', [
            'form' => $form->createView(),
            'loginTitle' => 'User Login',
        ]);
    }

    #[Route('/login-otp/resend/{id}', name: 'login_otp_resend', methods: ['POST'])]
    public function resendOtp(AppUser $user, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $lastSent = $session->get('otp_last_sent_'.$user->getId());

        if ($lastSent && (time() - $lastSent) < 60) {
            return $this->json([
                'success' => false,
                'message' => 'Please wait before requesting a new One Time Password.',
            ]);
        }

        $this->authOtpService->generateAndSendOtp($user);
        $session->set('otp_last_sent_'.$user->getId(), time());

        return $this->json(['success' => true]);
    }


    #[Route('/login-otp/verify/{id}', name: 'login_otp_verify', )]
    public function verify(
        AppUser $user,
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        AppUserAuthenticator $authenticator,
    ): Response {
    
        $form = $this->createForm(AuthOtpVerifyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $otp = $form->get('otp')->getData();

            if (empty($otp) || strlen($otp) < 4) {
                $this->addFlash('danger', 'Please enter a valid 4-digit One Time Password.');
                return $this->redirectToRoute('login_otp_verify', ['id' => $user->getId()]);
            }

            if ($this->authOtpService->verifyOtp($user, $otp)) {
                $this->addFlash('success', 'One Time Password verified successfully!');
                return $userAuthenticator->authenticateUser(
                    $user,
                    $authenticator,
                    $request
                );
            }

            $this->addFlash('danger', 'Invalid or expired One Time Password.');
        }

        return $this->render('otp/login_otp_verify.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'loginTitle' => 'User Login',
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
