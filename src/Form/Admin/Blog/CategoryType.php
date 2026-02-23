<?php

namespace App\Form\Admin\Blog;

use App\Entity\Blog\Category;
use App\Entity\Store;
use App\Form\Types\SeoMetaType;
use App\Helper\VichS3Helper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichImageType;

class CategoryType extends AbstractType
{

    public function __construct(private readonly VichS3Helper $vichS3Helper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('store', EntityType::class, [
            'class' => Store::class,
            'placeholder' => '-- Select Store --',
        ]);

        $builder->add('title', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('content', Type\TextareaType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('imageFile', VichImageType::class, [
            'label' => 'Image',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'imageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);
        $builder->add('seoMeta', SeoMetaType::class, [
            'label' => false,
        ]);
        $builder->add('enabled', Type\CheckboxType::class, [
            'label' => 'Enabled',
            'required' => false,
            'data' => true,
        ]);
        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
