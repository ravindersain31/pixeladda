<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Form\Admin\CloudFront\CloudFrontInvalidateType;
use App\Form\Admin\Configuration\AdminUserAddType;
use App\Form\Admin\Configuration\AdminUserChangePasswordType;
use App\Form\Admin\Configuration\AdminUserEditType;
use App\Form\Admin\Configuration\AdminUserFilterType;
use App\Repository\AdminUserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Aws\CloudFront\CloudFrontClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/config')]
class AdminUserController extends AbstractController
{
    #[Route('/users', name: 'config_users')]
    public function index(Request $request, AdminUserRepository $repository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $page = $request->query->getInt('page', 1);

        $form = $this->createForm(AdminUserFilterType::class, null, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);

        $query = $repository->listUsers();

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData(); 
            $query = $repository->filterUsersQuery($formData);
        }

        $users = $paginator->paginate($query, $page, 10);

        return $this->render('admin/configuration/admin-user/index.html.twig', [
            'users' => $users,
            'filterForm' => $form->createView(),
        ]);
    }

    #[Route('/cloud-cache', name: 'cloud_cache')]
    public function cloudFornt(Request $request): Response
    {
        $form = $this->createForm(CloudFrontInvalidateType::class);
        return $this->render('admin/configuration/admin-user/cloud-fornt-cache.html.twig', [
            'invalidateForm' => $form->createView(),
        ]);
    }

    #[Route('/cloudfront-invalidate', name: 'cloudfront_invalidate')]
    public function invalidateCloudFront(Request $request, ParameterBagInterface $params): Response
    {
        $region = $params->get('AWS_REGION');
        $distributionId = $params->get('AWS_DISTRIBUTION_ID');
        $form = $this->createForm(CloudFrontInvalidateType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', 'Invalid submission.');
            return $this->redirectToRoute('admin_cloud_cache');
        }
        $data = $form->getData();
        $invalidatePath = $data['invalidate_path'];
        if (empty($invalidatePath) || !str_starts_with($invalidatePath, '/')) {
            $this->addFlash('danger', 'Invalid path. It must start with `/`.');
            return $this->redirectToRoute('admin_cloud_cache');
        }

        $client = new CloudFrontClient([
            'version' => 'latest',
            'region'  => $region,
        ]);

        try {
            $result = $client->createInvalidation([
                'DistributionId' => $distributionId,
                'InvalidationBatch' => [
                    'Paths' => [
                        'Quantity' => 1,
                        'Items' => [$invalidatePath],
                    ],
                    'CallerReference' => uniqid('invalidate_', true),
                ],
            ]);

            $invalidationId = $result['Invalidation']['Id'] ?? 'N/A';
            $this->addFlash('success', 'Invalidation created for path: <code>' . htmlspecialchars($invalidatePath) . '</code>. ID: ' . $invalidationId);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error creating invalidation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_cloud_cache');
    }

    #[Route('/user/add', name: 'config_user_add')]
    public function userAdd(Request $request, AdminUserRepository $repository, PasswordHasherFactoryInterface $hasherFactory): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $form = $this->createForm(AdminUserAddType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $usernameExists = $repository->findOneBy(['username' => $form->get('username')->getData()]);
            $emailExists = $repository->findOneBy(['email' => $form->get('email')->getData()]);
            if ($usernameExists instanceof AdminUser || $emailExists instanceof AdminUser) {
                if ($usernameExists) {
                    $form->get('username')->addError(new FormError('This username is already taken by other user.'));
                }
                if ($emailExists) {
                    $form->get('email')->addError(new FormError('This email is already associated with other user.'));
                }
            } else {
                $newUser = new AdminUser();
                $newUser->setName($form->get('name')->getData());
                $newUser->setUsername($form->get('username')->getData());
                $newUser->setEmail($form->get('email')->getData());
                $newUser->setMobile($form->get('mobile')->getData());

                $hasher = $hasherFactory->getPasswordHasher($newUser);
                $newUser->setPassword($hasher->hash($form->get('password')->getData()));

                $roles = [];
                foreach ($form->get('roles')->getData() as $role) {
                    $roles[] = $role->getName();
                }
                $newUser->setRoles($roles);
                $newUser->setIsEnabled(true);
                $repository->save($newUser, true);
                $this->addFlash('success', 'New account has been created successfully.');
                return $this->redirectToRoute('admin_config_users');
            }
        }

        return $this->render('admin/configuration/admin-user/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/edit/{id}', name: 'config_user_edit')]
    public function userEdit(AdminUser $user, Request $request, AdminUserRepository $repository): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $form = $this->createForm(AdminUserEditType::class, null, ['user' => $user]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $emailExists = $repository->findOneBy(['email' => $form->get('email')->getData()]);
            if ($emailExists instanceof AdminUser && $emailExists !== $user) {
                $form->get('email')->addError(new FormError('This email is already associated with other user.'));
            } else {
                $user->setName($form->get('name')->getData());
                $user->setEmail($form->get('email')->getData());
                $user->setMobile($form->get('mobile')->getData());
                $user->setIsEnabled($form->get('enabled')->getData());

                $roles = [];
                foreach ($form->get('roles')->getData() as $role) {
                    $roles[] = $role->getName();
                }
                $user->setRoles($roles);
                $repository->save($user, true);
                $this->addFlash('success', 'Account has been updated successfully.');
                return $this->redirectToRoute('admin_config_users');
            }
        }

        return $this->render('admin/configuration/admin-user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/change-password/{id}', name: 'config_user_change_password')]
    public function userChangePassword(AdminUser $user, Request $request, AdminUserRepository $repository, PasswordHasherFactoryInterface $hasherFactory): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $form = $this->createForm(AdminUserChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hasher = $hasherFactory->getPasswordHasher($user);
            $user->setPassword($hasher->hash($form->get('password')->getData()));
            $repository->save($user, true);
            $this->addFlash('success', 'Account password has been changed successfully.');
            return $this->redirectToRoute('admin_config_users');
        }

        return $this->render('admin/configuration/admin-user/change-password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
