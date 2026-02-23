<?php

namespace App\Form\Admin\Configuration;

use App\Entity\Role;
use App\Form\Admin\Configuration\Field\RolePermissionField;
use App\Repository\RolePermissionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class RoleType extends AbstractType
{

    public function __construct(private readonly RolePermissionRepository $permissionRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Role $role */
        $role = $options['data'];
        if ($role->getPermissions() && count($role->getPermissions()) > 0) {
            $permissions = $this->permissionRepository->findPermissionsByName($role->getPermissions());
        }
        $builder
            ->add('name', TextType::class, [
                'label' => 'Role Name',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^ROLE_/',
                        'message' => 'Invalid Role Name',
                    ]),
                ],
                'help' => 'Role name should starts with ROLE_',
            ])
            ->add('title', TextType::class, [
                'label' => 'Role Title',
            ])
            ->add('permissions', RolePermissionField::class, [
                'mapped' => false,
                'required' => false,
                'data' => $permissions ?? [],
            ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Save',
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
