<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\ForgotPasswordType;
use App\Form\SetNewPasswordType;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request, UserRepository $repository, UserService $userService): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $user = $repository->findOneBy(['username' => $username]);
            if ($user instanceof User) {
                if ($user->getEmail()) {
                    $userService->createResetPasswordRequest($user, 'admin');
                    $this->addFlash('success', 'We have received your request to reset your password. You will receive an email shortly on your registered email address with instructions to reset the password.');
                    return $this->redirectToRoute('admin_login');
                } else {
                    $form->get('username')->addError(new FormError('We did not find any registered emails with this account. Please contact support.'));
                }
            } else {
                $form->get('username')->addError(new FormError('No account found with this username.'));
            }
        }
        return $this->render('admin/security/forgot-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/reset-password/{token}', name: 'reset_password')]
    public function resetPassword(string $token, Request $request, PasswordHasherFactoryInterface $passwordHasherFactory, UserRepository $repository, SessionInterface $session): Response
    {
        $user = $repository->findOneBy(['resetToken' => $token]);
        if (!$user instanceof User) {
            $this->addFlash('danger', 'This reset link is invalid or already used.');
            return $this->redirectToRoute('admin_forgot_password');
        }
        if ($user->getResetTokenExpireAt()->format('U') < (new \DateTimeImmutable())->format('U')) {
            $this->addFlash('danger', 'This reset link expired. Please create another request.');
            return $this->redirectToRoute('admin_forgot_password');
        }

        $form = $this->createForm(SetNewPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hasher = $passwordHasherFactory->getPasswordHasher($user);
            $password = $form->get('password')->getData();
            $user->setPassword($hasher->hash($password));
            $user->setResetToken(null);
            $user->setResetTokenExpireAt(null);
            $repository->save($user, true);
            $this->addFlash('success', 'Your account password has been changed successfully. Please login with new password.');
            $this->removeTargetPath($session, 'admin');
            return $this->redirectToRoute('admin_login');
        }
        return $this->render('admin/security/set-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
