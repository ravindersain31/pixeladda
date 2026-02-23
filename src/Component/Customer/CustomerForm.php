<?php

namespace App\Component\Customer;


use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\RolePermissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "CustomerForm",
    template: "components/customer-form.html.twig"
)]
class CustomerForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    public function __construct(private readonly RolePermissionRepository $permissionRepository)
    {
    }

    #[LiveProp(fieldName: 'formData')]
    public ?User $user;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            CustomerType::class,
            $this->user
        );
    }
}