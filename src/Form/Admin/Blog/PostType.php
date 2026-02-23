<?php

namespace App\Form\Admin\Blog;

use App\DataTransformer\DateTimeToImmutableTransformer;
use App\Entity\Blog\Author;
use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use App\Entity\Store;
use App\Form\Types\SeoMetaType;
use App\Helper\VichS3Helper;
use App\Validator\Constraints\AtLeastOneCategorySelected;
use Eckinox\TinymceBundle\Form\Type\TinymceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichImageType;

class PostType extends AbstractType
{

    public function __construct(private readonly VichS3Helper $vichS3Helper, private readonly UrlGeneratorInterface $urlGenerator)
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
        $builder->add('slug', Type\TextType::class, [
            'required' => false,
        ]);
        $builder->add('content', TinymceType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
            'attr' => [
                'height' => '700',
                'toolbar' => 'insertfile a11ycheck undo redo | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image | code',
                'plugins' => 'advcode advlist anchor autolink fullscreen help image tinydrive lists link media preview searchreplace table visualblocks wordcount',
                'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
                'images_upload_url' => $this->urlGenerator->generate('admin_blog_upload_file', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        ]);
        $builder->add('excerpt', Type\TextareaType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);
        $builder->add('categories', EntityType::class, [
            'class' => Category::class,
            'multiple' => true,
            'expanded' => true,
            'constraints' => [
                new AtLeastOneCategorySelected(),
            ],
        ]);
        $builder->add('imageFile', VichImageType::class, [
            'label' => 'Blog Image',
            'download_uri' => function (Post $post) {
                return $this->vichS3Helper->asset($post, 'imageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);
        $builder->add('seoMeta', SeoMetaType::class, [
            'label' => false,
        ]);
        $builder->add('author', EntityType::class, [
            'class' => Author::class,
            'placeholder' => '-- Select Author --',
        ]);
        $builder->add('publishedAt', Type\DateTimeType::class, [
            'label' => 'Published Date',
            'widget' => 'single_text',
            'required' => false,
        ]);
        $builder->get('publishedAt')->addModelTransformer(new DateTimeToImmutableTransformer());
        $builder->add('enabled', Type\CheckboxType::class, [
            'label' => 'Is Published',
            'required' => false,
        ]);
        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save Blog Post',
            'row_attr' => [
                'class' => 'm-0',
            ],
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
