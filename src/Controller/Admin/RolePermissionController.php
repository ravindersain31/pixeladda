<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Form\Admin\Configuration\RoleType;
use App\Repository\RolePermissionRepository;
use App\Repository\RoleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/config/roles')]
class RolePermissionController extends AbstractController
{
    #[Route('/', name: 'config_roles')]
    public function index(Request $request, RoleRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->query->getInt('page', 1);
        $query = $repository->listRoles();
        $roles = $paginator->paginate($query, $page, 10);

        return $this->render('admin/configuration/roles/index.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/add', name: 'config_role_add')]
    public function addStore(Request $request, RoleRepository $repository, RouterInterface $router): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($role->getName(), Role::RESERVED_ROLES)) {
                $form->get('name')->addError(new FormError('This Role Name is reserved. So you can\'t this. Please try different name.'));
            } else {
                $plainPermissions = [];
                foreach ($role->getPermissions() as $permission) {
                    $plainPermissions[] = $permission->getName();
                }
                $role->setPermissions($plainPermissions);
                $repository->save($role, true);
                $this->addFlash('success', 'Role added successfully');
                return $this->redirectToRoute('admin_config_roles');
            }
        }

        return $this->render('admin/configuration/roles/add.html.twig', [
            'form' => $form,
            'role' => $role,
        ]);
    }

    #[Route('/show/{name}', name: 'config_role_show')]
    public function showRole(#[MapEntity(mapping: ['name' => 'name'])] Role $role, Request $request, RoleRepository $repository, RolePermissionRepository $permissionRepository): Response
    {
        $permissions = $permissionRepository->findPermissionsByName($role->getPermissions());
        $role->setPermissions($permissions);

        return $this->render('admin/configuration/roles/show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/edit/{id}', name: 'config_role_edit')]
    public function editRole(Role $role, Request $request, RoleRepository $repository, RolePermissionRepository $permissionRepository): Response
    {
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($role->getName(), Role::RESERVED_ROLES)) {
                $form->get('name')->addError(new FormError('This Role Name is reserved. So you can\'t this. Please try different name.'));
            } else {
                $plainPermissions = [];
                foreach ($form->get('permissions')->getData() as $permission) {
                    $plainPermissions[] = $permission->getName();
                }
                $role->setPermissions($plainPermissions);
                $repository->save($role, true);
                $this->addFlash('success', 'Role updated successfully');
                return $this->redirectToRoute('admin_config_roles');
            }
        }
        return $this->render('admin/configuration/roles/edit.html.twig', [
            'form' => $form,
            'role' => $role,
        ]);
    }
}
