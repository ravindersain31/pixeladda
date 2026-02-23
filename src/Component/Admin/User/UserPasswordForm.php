<?php

namespace App\Component\Admin\User;

use App\Form\Admin\Customer\UserPasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "UserPasswordForm",
    template: "admin/customer/users/component/_update_password.html.twig"
)]
class UserPasswordForm extends AbstractController
{

    use DefaultActionTrait;
    use LiveCollectionTrait;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UserPasswordType::class);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

}