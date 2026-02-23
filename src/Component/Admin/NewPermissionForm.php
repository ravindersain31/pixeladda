<?php

namespace App\Component\Admin;

use App\Entity\RolePermission;
use App\Form\Admin\Configuration\RolePermissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsLiveComponent(
    name: "NewPermissionForm",
    template: "admin/components/new-permission-form.html.twig"
)]
class NewPermissionForm extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'formData')]
    public RolePermission $permission;

    private bool $shouldAutoSubmitForm = true;

    protected function instantiateForm(): FormInterface
    {
        $this->permission = new RolePermission();
        return $this->createForm(RolePermissionType::class, $this->permission);
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        $this->submitForm();

        $entityManager->persist($this->permission);
        $entityManager->flush();

        $this->dispatchBrowserEvent('modal:close');
        $this->emit('role:permission:created', [
            'permission' => $this->permission->getId(),
        ]);

        $this->resetForm();
    }

    #[PreReRender]
    public function submitFormOnRender(): void
    {
        if ($this->shouldAutoSubmitForm) {
            $this->shouldAutoSubmitForm = false;
            $this->submitForm($this->isValidated);
        }
    }

}