<?php

namespace App\Controller\Web\MyAccount;

use App\Entity\Address;
use App\Entity\AppUser;
use App\Entity\Order;
use App\Entity\SavedCart;
use App\Entity\EmailQuote;
use App\Entity\PaymentMethod;
use App\Entity\Referral;
use App\Entity\SavedDesign;
use App\Entity\User;
use App\Form\CustomerPasswordType;
use App\Form\CustomerType;
use App\Form\SaveAddressType;
use App\Helper\ReferralCodeHelper;
use App\Repository\EmailQuoteRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\AddressService;
use App\Service\SavedPaymentDetailService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\StoreInfoService;

#[Route(path: '/customer')]
class DashboardController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly StoreInfoService $storeInfoService)
    {
    }

    #[Route(path: '/', name: 'my_account')]
    public function myAccount(): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('login');
        }

        $user = $this->entityManager->getRepository(User::class)->find($this->getUser());    
        if($user->getIsTempPass()){
            $this->addFlash('info','You have logged in with Temporary Password, Please set your new Password to continue.');
            return $this->redirectToRoute('change_password');
        }
        return $this->render('customer/dashboard.html.twig', []);
    }

    #[Route(path: '/order-history', name: 'order_history')]
    public function orderHistory(Request $request, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        $sort = $request->query->get('sort', 'orderAt');
        $dir  = strtoupper($request->query->get('dir', 'DESC'));

        $allowedSorts = ['orderAt', 'orderId', 'totalAmount', 'status', 'paymentStatus'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'orderAt';
        }

        if (!in_array($dir, ['ASC', 'DESC'], true)) {
            $dir = 'DESC';
        }

        $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];

        $orders = $isPromoStore
            ? $orderRepository->findPromoOrdersByCustomer($user, $sort, $dir)
            : $orderRepository->findOrderByCustomer($user, $sort, $dir);

        return $this->render('customer/order_history.html.twig', [
            'orders' => $orders,
            'sort'   => $sort,
            'dir'    => $dir,
        ]);
    }

    #[Route(path: '/repeat-order-history', name: 'repeat_order_history')]
    public function repeatOrderHistory(Request $request): Response
    {
        $user = $this->getUser();
        $sort = $request->query->get('sort', 'orderAt');
        $dir  = strtoupper($request->query->get('dir', 'DESC'));

        $allowedSorts = ['orderAt', 'orderId', 'totalAmount', 'status', 'paymentStatus'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'orderAt';
        }

        if (!in_array($dir, ['ASC', 'DESC'], true)) {
            $dir = 'DESC';
        }
        $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];

        if($isPromoStore){
            $orders = $this->entityManager->getRepository(Order::class)->findPromoOrdersByCustomer($user, $sort, $dir);
        } else {
            $orders = $this->entityManager->getRepository(Order::class)->findOrderByCustomer($user, $sort, $dir);
        }
        return $this->render('customer/repeat_order_history.html.twig', [
            'orders' => $orders,
            'sort'   => $sort,
            'dir'    => $dir,
        ]);
    }

    #[Route(path: '/repeat-history', name: 'repeat_order')]
    public function repeatOrder(): Response
    {
        return $this->render('customer/dashboard.html.twig', []);
    }

    #[Route(path: '/account-setting', name: 'account_setting')]
    public function accountSetting(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        
        $user = $entityManager->getRepository(AppUser::class)->find($this->getUser());
        $form = $this->createForm(CustomerType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUsername($form->getData()->getEmail());
            $user->setName($form->getData()->getFirstName().' '. $form->getData()->getLastName());
            $userRepository->save($user, true);
            $this->addFlash('success', 'Updated successfully');
            return $this->redirectToRoute('account_setting');
        }
        
        return $this->render('customer/account_setting.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/change-password', name: 'change_password')]
    public function changePassword(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->entityManager->getRepository(AppUser::class)->find($this->getUser());
        $form = $this->createForm(CustomerPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPass = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPass);
            $user->setPassword($hashedPassword);
            $user->setIsTempPass(false);
            $userRepository->save($user, true);
            $this->addFlash('success', 'Password Updated Successfully');
            return $this->redirectToRoute('my_account');
        }

        return $this->render('customer/change_password.html.twig', [
            'form' => $form
        ]);
    }

    #[Route(path: '/save-design', name: 'save_design')]
    public function saveDesign(): Response
    {
        $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];
        $user = $this->entityManager->getRepository(AppUser::class)->find($this->getUser());
        if($isPromoStore){
            $savedDesigns = $this->entityManager->getRepository(SavedDesign::class)->findPromoSavedDesignCustomer($user);
        } else {
            $savedDesigns = $this->entityManager->getRepository(SavedDesign::class)->findSavedDesignCustomer($user);
        }

        return $this->render('customer/saved_designs.html.twig', [
            'savedDesigns' => $savedDesigns
        ]);
    }

    #[Route(path: '/saved-cart', name: 'saved_card')]
    public function savedCart(): Response
    {
        $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];
        $user = $this->entityManager->getRepository(AppUser::class)->find($this->getUser());
        if($isPromoStore){
            $savedCarts = $this->entityManager->getRepository(SavedCart::class)->findPromoSavedCartCustomer($user);
        } else {
            $savedCarts = $this->entityManager->getRepository(SavedCart::class)->findSavedCartCustomer($user);
        }
        return $this->render('customer/saved_carts.html.twig', [
            'savedCarts' => $savedCarts
        ]);
    }

    #[Route(path: '/email-quote', name: 'email_quote')]
    public function emailQuote(): Response
    {
        $isPromoStore = $this->storeInfoService->storeInfo()['isPromoStore'];
        $user = $this->entityManager->getRepository(AppUser::class)->find($this->getUser());
        if($isPromoStore){
            $emailQuotes = $this->entityManager->getRepository(EmailQuote::class)->findPromoEmailQuoteCustomer($user);
        } else {
            $emailQuotes = $this->entityManager->getRepository(EmailQuote::class)->findEmailQuoteCustomer($user);
        }
        return $this->render('customer/email_quote.html.twig', [
            'emailQuotes' => $emailQuotes
        ]);
    }

    #[Route(path: '/remove-email-quote/{id}', name: 'remove_email_quote')]
    public function removeSaveQuote(EmailQuote $emailQuote, EmailQuoteRepository $emailQuoteRepository): Response
    {
        try {
            if ($emailQuote instanceof EmailQuote && $emailQuote->getUser() === $this->getUser()) {
                $emailQuoteRepository->remove($emailQuote, true);
                $this->addFlash('success', 'Email Quote removed successfully');
            } else {
                $this->addFlash('danger', 'You are not allowed to remove this Email Qoute');
            }
        } catch (Exception $e) {
            $this->addFlash('danger', 'An error occurred: ' . $e->getMessage());
        }

        return $this->redirectToRoute('email_quote');
    }

    #[Route(path: 'invite-friends', name: 'invite_friends')]
    public function inviteFriends(ReferralCodeHelper $referralCodeHelper, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'You must be logged in to invite friends.');
            return $this->redirectToRoute('login'); 
        }

        $referralUrl = '';
        $existingReferral = $entityManager->getRepository(Referral::class)->findOneBy(['referrer' => $user]);

        if ($existingReferral) {
            $referralUrl = $this->generateUrl('create_account', ['referralCode' => $existingReferral->getReferralCode()], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $referralCode = $referralCodeHelper->generateUniqueReferralCode($user);

            $referral = new Referral();
            $referral->setReferralCode($referralCode);
            $referral->setReferrer($user); 
            $entityManager->persist($referral);
            $entityManager->flush();

            $referralUrl = $this->generateUrl('create_account', ['referralCode' => $referralCode], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->render('customer/invite_friends.html.twig', [
            'referralUrl' => $referralUrl
        ]);
    }

    #[Route(path: '/saved-addresses', name: 'saved_addresses')]
    public function savedAddresses(Request $request, EntityManagerInterface $entityManager, AddressService $addressService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        $address = new Address();
        $form = $this->createForm(SaveAddressType::class, $address);

        $addresses = $addressService->getAllAddresses($this->getUser());

        return $this->render('customer/saved_addresses.html.twig', [
            'addresses' =>  $addresses ?? [],
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/saved-cards', name: 'saved_cards')]
    public function savedCards(SavedPaymentDetailService $savedPaymentDetailService): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $savedCards = $savedPaymentDetailService->getSavedPaymentDetails($user); 

        return $this->render('customer/saved_cards.html.twig', [
            'savedCards' => $savedCards,
        ]);
    }
}