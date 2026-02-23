<?php

namespace App\Form\Admin;

use App\Entity\Category;
use App\Entity\Store;
use App\Form\Types\SeoMetaType;
use App\Helper\VichS3Helper;
use Doctrine\ORM\EntityManagerInterface;
use Eckinox\TinymceBundle\Form\Type\TinymceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bundle\SecurityBundle\Security;

class CategoryType extends AbstractType
{

    public function __construct(
        private readonly VichS3Helper           $vichS3Helper,
        private readonly UrlGeneratorInterface  $urlGenerator,
        private readonly Security               $security,
        private readonly EntityManagerInterface $entityManagerInterface
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Category|null $category */
        $category = $options['data'];

        $displayLayoutKeys = [
            Category::SIZE_SPECIFIC,
            Category::LIST_VIEW,
        ];

        $displayLayoutChoices = array_intersect_key(Category::DISPLAY_LAYOUT, array_flip($displayLayoutKeys));

        if (!$category->getParent() && count($category->getSubCategories()) > 0) {
            $displayLayoutChoices = Category::DISPLAY_LAYOUT;
        }

        $builder->add('name', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('skuInitial', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        if ($options['data']->getId()) {
            $builder->add('slug', Type\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ]);
        }
        $builder->add('store', EntityType::class, [
            'class' => Store::class,
            'choice_label' => 'name',
            'expanded' => true,
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('sortPosition', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Range([
                    'min' => 0,
                    'max' => 500,
                ]),
            ],
        ]);

        $builder->add('displayLayout', Type\ChoiceType::class, [
            'label' => 'Display Layout',
            'placeholder' => '-- Select Layout --',
            'choices' => array_flip($displayLayoutChoices),
            'help' => 'Default: Size Specific',
            'required' => false,
            'expanded' => false,
            'multiple' => false,
        ]);

        $builder->add('parent', EntityType::class, [
            'label' => 'Parent Category',
            'class' => Category::class,
            'choice_label' => 'name',
            'query_builder' => function () use ($category) {
                $qb = $this->entityManagerInterface->getRepository(Category::class)
                    ->createQueryBuilder('c')
                    ->where('c.parent IS NULL');
                if ($category && $category->getId()) {
                    $qb->andWhere('c.id != :currentCategory')
                        ->setParameter('currentCategory', $category->getId());
                }
                return $qb;
            },
            'auto_initialize' => false,
            'invalid_message' => false,
            'autocomplete' => true,
            'multiple' => false,
            'required' => false,
        ]);

        $builder->add('isEnabled', Type\CheckboxType::class, [
            'label' => 'Enabled this Category',
            'required' => false,
        ]);
        $builder->add('displayInMenu', Type\CheckboxType::class, [
            'label' => 'Display In Menu',
            'help' => 'If this is ticked, it will display the category in the menu.',
            'required' => false,
        ]);
        $builder->add('bannerFile', VichImageType::class, [
            'label' => 'Desktop Banner',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'bannerFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);
        $builder->add('mobileBannerFile', VichImageType::class, [
            'label' => 'Mobile Banner',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'mobileBannerFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('promoBannerFile', VichImageType::class, [
            'label' => 'Promo Desktop Banner',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'promoBannerFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);
        
        $builder->add('promoMobileBannerFile', VichImageType::class, [
            'label' => 'Promo Mobile Banner',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'promoMobileBannerFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);
        
        $builder->add('thumbnailFile', VichImageType::class, [
            'label' => 'Shop Page Thumbnail',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'thumbnailFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('promoThumbnailFile', VichImageType::class, [
            'label' => 'Promo Thumbnail',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'promoThumbnailFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('categoryThumbnailFile', VichImageType::class, [
            'label' => 'Category Thumbnail',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'categoryThumbnailFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('promoCategoryThumbnailFile', VichImageType::class, [
            'label' => 'Promo Category Thumbnail',
            'download_uri' => function (Category $category) {
                return $this->vichS3Helper->asset($category, 'promoCategoryThumbnailFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('seoMeta', SeoMetaType::class);
        $builder->add('productSeoMeta', SeoMetaType::class);
        $builder->add('description', TinymceType::class, [
            'label' => 'Product Information',
            'attr' => [
                'height' => '400',
                'toolbar' => 'insertfile a11ycheck undo redo | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image | code',
                'plugins' => 'advcode advlist anchor autolink fullscreen help image tinydrive lists link media preview searchreplace table visualblocks wordcount',
                'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
                'images_upload_url' => $this->urlGenerator->generate('admin_blog_upload_file', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        ]);
        $builder->add('productDescription', TinymceType::class, [
            'label' => 'Product Description',
            'required' => false,
            'attr' => [
                'height' => '400',
                'toolbar' => 'insertfile a11ycheck undo redo | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image | code',
                'plugins' => 'advcode advlist anchor autolink fullscreen help image tinydrive lists link media preview searchreplace table visualblocks wordcount',
                'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
                'images_upload_url' => $this->urlGenerator->generate('admin_blog_upload_file', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
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
//            'csrf_token_id' => 'category_form',
        ]);
    }
}
