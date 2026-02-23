<?php

namespace App\Form\Admin\Blog;

use App\Entity\Blog\Author;
use App\Helper\VichS3Helper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichImageType;

class AuthorType extends AbstractType
{

    public function __construct(private readonly VichS3Helper $vichS3Helper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('name', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);

        $builder->add('nickname', Type\TextType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Regex([
                    'pattern' => '/^[a-z0-9_]+$/',
                    'message' => 'Only lowercase letters, numbers and underscores are allowed',
                ])
            ],
        ]);

        $builder->add('bio', Type\TextareaType::class, [
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);

        $builder->add('avatarFile', VichImageType::class, [
            'label' => 'Avatar',
            'download_uri' => function (Author $author) {
                return $this->vichS3Helper->asset($author, 'avatarFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
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
            'data_class' => Author::class,
        ]);
    }
}
