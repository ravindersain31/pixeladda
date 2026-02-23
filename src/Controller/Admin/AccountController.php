<?php

namespace App\Controller\Admin;

use App\Entity\StoreSettings;
use App\Entity\Store;
use App\Form\Admin\ChangePasswordType;
use App\Form\Admin\Configuration\StoreSettingsType;
use App\Form\Admin\Configuration\StoreType;
use App\Repository\StoreSettingsRepository;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class AccountController extends AbstractController
{
    #[Route('/', name: 'account_profile')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'account_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, PasswordHasherFactoryInterface $passwordHasherFactory): Response
    {
        $user = $this->getUser();

        $passwordHasher = $passwordHasherFactory->getPasswordHasher($user);
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            if ($passwordHasher->verify($user->getPassword(), $currentPassword)) {
                $newPassword = $form->get('password')->getData();
                $hashedPassword = $passwordHasher->hash($newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'You account password has been changed successfully. Please use your new password which is "' . $newPassword . '" from next login.');
                return $this->redirectToRoute('admin_account_profile');
            }
            $form->get('currentPassword')->addError(new FormError('Current password is incorrect. Please enter the correct current password.'));
        }

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

}
