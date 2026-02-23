<?php

namespace App\Component\Admin;

use App\Entity\AppUser;
use App\Form\Admin\Customer\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "UserForm",
    template: "admin/customer/users/component/form.html.twig"
)]
class UserForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?AppUser $user;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            UserType::class,
            $this->user
        );
    }
}