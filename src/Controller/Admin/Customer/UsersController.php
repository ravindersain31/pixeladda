<?php

namespace App\Controller\Admin\Customer;

use App\Entity\Admin\Coupon;
use App\Entity\AppUser;
use App\Entity\Artwork;
use App\Entity\Order;
use App\Entity\Reward\RewardTransaction;
use App\Enum\WholeSellerEnum;
use App\Form\Admin\Customer\ArtworkFilterType;
use App\Form\Admin\Coupon\CouponType;
use App\Form\Admin\Customer\FilterCustomerType;
use App\Form\Admin\Customer\Reward\RewardTransactionType;
use App\Form\Admin\Customer\UserPasswordType;
use App\Form\Admin\Customer\UserType;
use App\Helper\ImageHelper;
use App\Helper\UploaderHelper;
use App\Form\Admin\Customer\ArtworkImageUpdateType;
use App\Form\Admin\Customer\Reward\RewardTransferType;
use App\Repository\AppUserRepository;
use App\Repository\ArtworkRepository;
use App\Repository\ReferralRepository;
use App\Repository\Reward\RewardTransactionRepository;
use App\Repository\UserRepository;
use App\Service\ExportService;
use App\Service\Reward\RewardService;
use App\Service\StoreInfoService;
use Symfony\Component\Mime\Address;
use App\Enum\StoreConfigEnum;
use App\Helper\PromoStoreHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/users', name: 'users_')]
class UsersController extends AbstractController
{
    #[Route('/customers', name: 'customers')]
    public function users(Request $request, AppUserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));
        $filterForm = $this->createForm(FilterCustomerType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);
        $query = $userRepository->filterUsers(
            email:  $filterForm->get('email')->getData(),
            name:  $filterForm->get('name')->getData(),
        );

        $page = $request->get('page', 1);
        $users = $paginator->paginate($query, $page, 32);

        return $this->render('admin/customer/users/index.html.twig', [
            'users' => $users,
            'filterForm' => $filterForm,
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
        ]);
    }

    #[Route('wholeSeller/view/{id}', name: 'whole_seller_view')]
    public function view(Request $request,AppUser $customer): Response
    {
        return $this->render('admin/customer/whole_seller/view.html.twig',[
            'customer' => $customer
        ]);
    }

    #[Route('/whole-seller/{slug}', name: 'whole_seller', requirements: ['slug' => 'list|accepted|rejected'])]
    public function wholeSellerList(
        Request $request,
        AppUserRepository $userRepository,
        PaginatorInterface $paginator,
        string $slug = 'list',
    ): Response {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $filterForm = $this->createForm(FilterCustomerType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);

        $status = null;
        $template = 'admin/customer/whole_seller/index.html.twig';

        switch ($slug) {
            case 'accepted':
                $status = WholeSellerEnum::ACCEPTED;
                $template = 'admin/customer/whole_seller/accepted.html.twig';
                break;
            case 'rejected':
                $status = WholeSellerEnum::REJECTED;
                $template = 'admin/customer/whole_seller/rejected.html.twig';
                break;
            default:
                $status = null;
                $template = 'admin/customer/whole_seller/index.html.twig';
                break;
        }

        $query = $userRepository->findWholeSellersQuery(
            email: $filterForm->get('email')->getData(),
            name: $filterForm->get('name')->getData(),
            status: $status
        );

        $page = $request->get('page', 1);
        $users = $paginator->paginate($query, $page, 32);

        return $this->render($template, [
            'users' => $users,
            'filterForm' => $filterForm,
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
            'slug' => $slug,
        ]);
    }

    #[Route('/whole-seller/{id}/accept', name: 'whole_seller_accept')]
    public function accept(
        AppUser $customer,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        PromoStoreHelper $promoStoreHelper,
    ): Response {
       
        $customer->setWholeSellerStatus(WholeSellerEnum::ACCEPTED);
        $em->flush();
        $domain = $customer->getWholeSeller()?->getStoreDomain();

        $loginUrl = $urlGenerator->generate(
            'whole_seller_login',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $loginUrl = $promoStoreHelper->storeBasedUrl($loginUrl, $domain);

        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, "Yard Sign Promo"));
        $email->subject("Welcome to ". "Yard Sign Promo");
        $email->to($customer->getEmail());
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, "YardSignPromo"));
        $email->htmlTemplate('emails/wholeseller/accepted.html.twig')->context([
            'customer' => $customer,
            'loginUrl' => $loginUrl
        ]);
        $mailer->send($email);

        $this->addFlash('success', 'Wholeseller request accepted.');
        return $this->redirectToRoute('admin_users_whole_seller', ['slug' => 'list']);

    }

    #[Route('/whole-seller/{id}/reject', name: 'whole_seller_reject')]
    public function reject(
        AppUser $customer,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
       
        $customer->setWholeSellerStatus(WholeSellerEnum::REJECTED);
        $em->flush();

        $email = (new TemplatedEmail());
        $email->from(new Address(StoreConfigEnum::SALES_EMAIL, "YardSignPromo"));
        $email->subject("Welcome to ". "Yard Sign Promo");
        $email->to($customer->getEmail());
        $email->cc(new Address(StoreConfigEnum::SALES_EMAIL, "YardSignPromo"));
        $email->htmlTemplate('emails/wholeseller/rejected.html.twig')->context([
            'customer' => $customer,
        ]);
        $mailer->send($email);

        $this->addFlash('danger', 'Wholeseller request rejected.');
        return $this->redirectToRoute('admin_users_whole_seller', ['slug' => 'list']);

    }

    #[Route('/customer/reward-transfer/{id}', name: 'customers_reward_transfer')]
    public function rewardTransfer(
        AppUser $fromUser,
        Request $request,
        AppUserRepository $userRepository,
        RewardService $rewardService
    ): Response {
        
        if (!$fromUser) {
            throw $this->createNotFoundException('User not found.');
        }

        $availablePoints = $fromUser->getReward() ? $fromUser->getReward()->getAvailablePoints() : 0;

        $form = $this->createForm(RewardTransferType::class, null, [
            'fromEmail' => $fromUser->getEmail(),
            'availableReward' => $availablePoints,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $amountToTransfer = $data['amount'];
            $toUser = $userRepository->findOneBy(['email' => $data['toEmail']]);

            if (!$toUser) {
                $this->addFlash('danger', 'Recipient email not found.');
            } elseif ($availablePoints < $amountToTransfer) {
                $this->addFlash('danger', 'Insufficient reward balance.');
            } else {
                $rewardService->updateRewardPoints(
                    reward: $fromUser->getReward(),
                    points: $amountToTransfer,
                    comment: sprintf('Transferred to %s', $fromUser->getEmail()),
                    type: RewardTransaction::DEBIT,
                    user: $fromUser,
                    status: RewardTransaction::STATUS_COMPLETED
                );

                $rewardService->getOrCreateReward($toUser);
                $rewardService->updateRewardPoints(
                    reward: $toUser->getReward(),
                    points: $amountToTransfer,
                    comment: sprintf('Received from %s', $toUser->getEmail()),
                    type: RewardTransaction::CREDIT,
                    user: $toUser,
                    status: RewardTransaction::STATUS_COMPLETED
                );

                $this->addFlash('success', 'Reward transferred successfully!');
                return $this->redirectToRoute('admin_users_customers');
            }
        }

        return $this->render('admin/customer/users/reward_transfer.html.twig', [
            'form' => $form->createView(),
            'fromUser' => $fromUser,
            'availableReward' => $availablePoints,
        ]);
    }


    #[Route('/customer/edit/{id}', name: 'customer_edit')]
    public function edit(AppUser $user, Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));
        try{
            if(!$user){
                $this->addFlash('danger', 'User not found');
                return $this->redirectToRoute('admin_users_customers');
            }
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user->setName($data->getFirstName().' '.$data->getLastName());
                $user->setUsername($data->getEmail());
                $userRepository->save($user, true);
                $this->addFlash('success', 'User has been updated successfully.');
                return $this->redirectToRoute('admin_users_customer_edit', ['id' => $user->getId()]);
            }
            return $this->render('admin/customer/users/edit/index.html.twig', [
                'user' => $user,
                'form' => $form
            ]);

        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customers');
        }
    }

    #[Route('/customer/reward-points/{id}', name: 'customer_reward_points')]
    public function rewardPoints(AppUser $user, RewardService $rewardService, Request $request, PaginatorInterface $paginator): Response
    {

        $this->denyAccessUnlessGranted($request->get('_route'));

        try {
            if(!$user){
                $this->addFlash('danger', 'User not found');
                return $this->redirectToRoute('admin_users_customers');
            }

            $rewardService->getOrCreateReward($user);

            $rewardTransaction = new RewardTransaction();

            $form = $this->createForm(RewardTransactionType::class, $rewardTransaction);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                if(!in_array($data->getType(), RewardTransaction::TRANSACTION_TYPES)){
                    $this->addFlash('danger', 'Invalid transaction type.');
                    return $this->redirectToRoute('admin_users_customer_reward_points', ['id' => $user->getId()]);
                }

                $rewardService->updateRewardPoints(
                    reward: $user->getReward(),
                    points: $data->getPoints(),
                    comment: $data->getComment(),
                    type: $data->getType(),
                    user: $this->getUser(),
                    status: RewardTransaction::STATUS_COMPLETED,
                );
                $this->addFlash('success', 'Reward has been added successfully.');
                return $this->redirectToRoute('admin_users_customer_reward_points', ['id' => $user->getId()]);
            }

            $page = $request->get('page', 1);
            $transactions = $paginator->paginate($user->getReward()->getRewardTransactions(), $page, 32);

            return $this->render('admin/customer/users/edit/index.html.twig', [
                'transactions' => $transactions,
                'user' => $user,
                'rewardTransaction' => $rewardTransaction,
                'form' => $form
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customers');
        }
    }

    #[Route('/customer/{id}/update-password', name: 'customer_update_password')]
    public function updatePassword(AppUser $user, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {

        $this->denyAccessUnlessGranted($request->get('_route'));
        try{
            if(!$user){
                $this->addFlash('danger', 'User not found');
                return $this->redirectToRoute('admin_users_customers');
            }

            $form = $this->createForm(UserPasswordType::class, []);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
                $userRepository->save($user, true);
                $this->addFlash('success', 'User password has been updated successfully.');
                return $this->redirectToRoute('admin_users_customer_update_password', ['id' => $user->getId()]);
            }
            return $this->render('admin/customer/users/edit/index.html.twig', [
                'user' => $user,
                'userPasswordform' => $form
            ]);
        }catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customer_update_password',);
        }
    }

    #[Route('/customer/reward-transaction/remove/{id}/{userId}', name: 'customer_reward_transaction_remove')]
    public function removeTransaction(RewardTransaction $transaction, $userId, Request $request, RewardTransactionRepository $rewardTransactionRepository): Response
    {

        try{
            $rewardTransactionRepository->remove($transaction, true);
            $this->addFlash('success', 'Transaction has been removed successfully.');
            return $this->redirectToRoute('admin_users_customer_reward_points', ['id' => $userId]);
        }catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customer_reward_points', ['id' => $userId]);
        }
    }


    #[Route('/customer/reward-points/view/{id}', name: 'customer_reward_points_view')]
    public function viewRewardPoints(RewardTransaction $transaction, RewardTransactionRepository $rewardTransactionRepository, Request $request): Response
    {

        $this->denyAccessUnlessGranted($request->get('_route'));

        try {

            $form = $this->createForm(RewardTransactionType::class, $transaction);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                if(!in_array($data->getType(), RewardTransaction::TRANSACTION_TYPES)){
                    $this->addFlash('danger', 'Invalid transaction type.');
                    return $this->redirectToRoute('admin_users_customer_reward_points_view', ['id' => $transaction->getId()]);
                }

                $rewardTransactionRepository->save($data, true);

                $this->addFlash('success', 'Transaction has been updated successfully.');
                return $this->redirectToRoute('admin_users_customer_reward_points_view', ['id' => $transaction->getId()]);
            }
            
            return $this->render('admin/customer/users/reward/view.html.twig', [
                'transaction' => $transaction,
                'form' => $form
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customers');
        }
    }

    #[Route('/export-users-orders', name: 'users_order_export')]
    public function exportUsersOrderCsv(Request $request, EntityManagerInterface $entityManager, ExportService $exportService): Response
    {
        try {
            $this->denyAccessUnlessGranted($request->get('_route'));
            $orders = $entityManager->getRepository(Order::class)->findAll();
            $filename = 'users_' . date('YmdHis') . '.csv';
            $exportService->exportUsersWithAddress($orders, $filename);
            return $this->redirectToRoute('admin_users_customers');

        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customers');
        }
    }

    #[Route('/customer/{id}/referral-coupons', name: 'customer_referral_coupons')]
    public function referralCoupons(AppUser $user, Request $request, ReferralRepository $referralRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        try {
            if(!$user){
                $this->addFlash('danger', 'User not found');
                return $this->redirectToRoute('admin_users_customers');
            }

            $allReferralCoupons = $referralRepository->getReferralCouponsByReferrer($user);
            $page = $request->get('page', 1);
            $referralCoupons = $paginator->paginate($allReferralCoupons, $page, 32);

            $coupon = new Coupon();
            $form = $this->createForm(CouponType::class, $coupon);

            return $this->render('admin/customer/users/edit/index.html.twig', [
                'form' => $form,
                'user' => $user,
                'coupon' => $coupon,
                'referralCoupons' => $referralCoupons,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('admin_users_customers');
        }
    }

    #[Route('/customer/{id}/referral-coupon/{couponId}/edit', name: 'customer_referral_coupon_edit')]
    public function editReferralCoupon(AppUser $user, int $couponId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));
    
        if (!$user) {
            $this->addFlash('danger', 'User not found');
            return $this->redirectToRoute('admin_users_customers');
        }
    
        $coupon = $entityManager->getRepository(Coupon::class)->find($couponId);

        if (!$coupon) {
            $this->addFlash('danger', 'Referral coupon not found');
            return $this->redirectToRoute('admin_users_customer_referral_coupons', ['id' => $user->getId()]);
        }
    
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            $this->addFlash('success', 'Referral coupon updated successfully!');
            return $this->redirectToRoute('admin_users_customer_referral_coupons', ['id' => $user->getId()]);
        }

        return $this->render('admin/customer/users/referral_coupon/edit.html.twig', [
            'form' => $form,
            'user' => $user,
            'coupon' => $coupon,
        ]);
    }

    #[Route('/update-artwork-image-list', name: 'update_artwork_image_list')]
    public function index(
        Request $request,
        ArtworkRepository $artworkRepository,
        PaginatorInterface $paginator
    ): Response {

        $filterForm = $this->createForm(ArtworkFilterType::class);
        $filterForm->handleRequest($request);

        $filters = $filterForm->isSubmitted() && $filterForm->isValid() ? $filterForm->getData() : [];
        $query = $artworkRepository->getFilteredArtwork($filters)->getQuery();

        $artworks = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/customer/artwork/index.html.twig', [
            'artworks' => $artworks,
            'filterForm' => $filterForm->createView(),
        ]);
    }


   #[Route('/update-artwork-image/{id}', name: 'update_artwork_image')]
    public function updateImageName(
        Request $request,
        EntityManagerInterface $em,
        Artwork $artwork
    ): Response {
        if (!$artwork) {
            $this->addFlash('error', 'Artwork not found.');
            return $this->redirectToRoute('admin_users_update_artwork_image_list');
        }
        $newImageNameFromUrl = $request->query->get('newImageName');

        $form = $this->createForm(ArtworkImageUpdateType::class, [
            'old_image_name' => $artwork->getImage()->getName() ?? '',
            'new_image_name' => $newImageNameFromUrl ?? '',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newName = $data['new_image_name'];

            $artwork->setImageName($newName);
            $em->flush();

            $this->addFlash('success', 'Artwork image updated successfully.');
            return $this->redirectToRoute('admin_users_update_artwork_image_list');
        }

        return $this->render('admin/customer/artwork/update_image.html.twig', [
            'form' => $form->createView(),
            'artwork' => $artwork,
        ]);
    }

    #[Route('/artwork/update-image-url/{id}', name: 'update_artwork_image_url')]
    public function updateArtworkImageUrl(
        Artwork $artwork,
        UploaderHelper $uploader,
        ImageHelper $imageHelper
    ): Response {
        if (!$artwork || !$artwork->getImage()) {
            $this->addFlash('error', 'Artwork not found.');
            return $this->redirectToRoute('admin_users_update_artwork_image_list');
        }

        $imageName = $artwork->getImage()->getName();
        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $fileName = pathinfo($imageName, PATHINFO_FILENAME);

        $newImageName = null;

        if ($ext === 'gif') {
            $imageUrl = "https://static.yardsignplus.com/clipart/{$imageName}";

            $uploadedFile = $uploader->getUploadedFileFromUrl($imageUrl);
            $converted = $imageHelper->toPng($uploadedFile->getRealPath());

            if (!$converted['success']) {
                $this->addFlash('error', 'Failed to convert GIF to PNG.');
                return $this->redirectToRoute('admin_users_update_artwork_image_list');
            }

            $pngFile = $uploader->createFileFromContents($converted['blob'], $fileName . '.png');
            $url = $uploader->upload($pngFile, 'clipartStorage');

            if (!$url) {
                $this->addFlash('error', 'Failed to upload PNG image.');
                return $this->redirectToRoute('admin_users_update_artwork_image_list');
            }

            $parsedUrl = parse_url($url, PHP_URL_PATH);
            $newImageName = basename($parsedUrl);
            $this->addFlash('success', 'Artwork GIF converted to PNG.');
        } else {
            $this->addFlash('info', 'Image is not a GIF, no conversion needed.');
        }

        return $this->redirectToRoute('admin_users_update_artwork_image', [
            'id' => $artwork->getId(),
            'newImageName' => $newImageName
        ]);
    }
}
