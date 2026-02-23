<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseLabel;
use App\Form\Admin\Warehouse\LabelType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseLabelForm",
    template: "admin/warehouse/components/label-form.html.twig"
)]
class LabelFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?WarehouseLabel $warehouseLabel;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            LabelType::class,
            $this->warehouseLabel
        );
    }

    #[LiveAction]
    public function save(): Response|null
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $label = $form->getData();
            $this->entityManager->persist($label);
            $this->entityManager->flush();
            $this->addFlash('success', 'Label has been updated successfully');
            return $this->redirectToRoute('admin_warehouse_label_list');
        }
        return null;
    }
}
