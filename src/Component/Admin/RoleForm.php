<?php

namespace App\Component\Admin;


use App\Entity\Role;
use App\Form\Admin\Configuration\RoleType;
use App\Repository\RolePermissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminRoleForm",
    template: "admin/components/role-form.html.twig"
)]
class RoleForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    public function __construct(private readonly RolePermissionRepository $permissionRepository)
    {
    }

    #[LiveProp(fieldName: 'formData')]
    public ?Role $role;

    protected function instantiateForm(): FormInterface
    {
        $permissions = $this->permissionRepository->findPermissionsByName($this->role->getPermissions());
        $this->role->setPermissions($permissions);

        return $this->createForm(
            RoleType::class,
            $this->role
        );
    }
}
