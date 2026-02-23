<?php

namespace App\Controller\Web\MyAccount;

use App\Entity\AppUser;
use App\Form\CreateAccountType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/create-account', name: 'create_account')]
    public function createAccount(): Response
    {
        return $this->render('customer/Auth/create_account.html.twig', [
            'accountType' => 'user',
            'title' => 'Create Account',
            'signinRoute' => 'login',
            'loginTitle'=> 'Login Here',
            'wholeSellerShow' => false,
            'createAccount' => 'Create Account',
        ]);

    }

    #[Route(path: '/whole-seller-create-account', name: 'whole_seller_create_account')]
    public function wholeSellerCreateAccount(): Response
    {
        return $this->render('customer/Auth/create_account.html.twig', [
            'accountType' => 'whole_seller',
            'title' => 'Wholesaler Sign Up',
            'signinRoute' => 'whole_seller_login',
            'loginTitle'=> 'Wholesaler Login Here',
            'wholeSellerShow' => true,
            'createAccount' => 'Wholesaler Sign Up',
        ]);
    }

    #[Route(path: '/forget-password', name: 'reset_password')]
    public function forgetPassword(): Response
    {
        return $this->render('customer/Auth/reset_password.html.twig', []);
    }
}
