<?php

namespace App\Form\Admin\Configuration\Field;

use App\Entity\RolePermission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class RolePermissionField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => RolePermission::class,
            'label' => 'Permissions',
            'choice_label' => 'name',
            'multiple' => true,
            'max_results' => 20,
            'preload' => true,
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
