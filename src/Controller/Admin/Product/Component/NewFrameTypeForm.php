<?php

namespace App\Controller\Admin\Product\Component;

use App\Entity\Category;
use App\Entity\ProductType;
use App\Form\Admin\ProductType\NewFrameType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "NewFrameTypeForm",
    template: "admin/product/types/component/new_frame_type.html.twig"
)]

class NewFrameTypeForm extends AbstractController
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {

    }

    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?ProductType $productType;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(NewFrameType::class, null, []);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->validate();
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $newFrameTypeName = 'pricing_'.$data['frameType'];

        // Add new frame type if it doesn't already exist
        $framePricing = $this->productType->getFramePricing();

        if (!array_key_exists($newFrameTypeName, $framePricing)) {
            $framePricing[$newFrameTypeName] = [];
            $this->productType->setFramePricing($framePricing);
            $this->entityManager->persist($this->productType);
            $this->entityManager->flush();
            $this->addFlash('success', 'Frame type has been created successfully.');
            return $this->redirectToRoute('admin_product_type_frame', ['id' => $this->productType->getId()]);
        }
        
        $this->addFlash('danger', 'Frame type already exists.');
        return $this->redirectToRoute('admin_product_type_frame', ['id' => $this->productType->getId()]);
    }
}
