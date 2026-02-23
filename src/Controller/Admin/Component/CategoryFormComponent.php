<?php

namespace App\Controller\Admin\Component;


use App\Entity\Category;
use App\Form\Admin\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminCategoryForm",
    template: "admin/category/component/form.html.twig"
)]
class CategoryFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Category $category;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            CategoryType::class,
            $this->category
        );
    }
}
