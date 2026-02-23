<?php


namespace App\Service;

use App\Repository\RolePermissionRepository;
use App\Repository\RoleRepository;

class RBACManager
{
    public function __construct(private readonly RoleRepository $roleRepository)
    {
    }

    public function getPermissions(array $roles): array
    {
        $roles = $this->roleRepository->findByRoles($roles);
        $permissions = [];
        foreach ($roles as $role) {
            $permissions += $role->getPermissions();
        }

        return array_unique($permissions);
    }
}