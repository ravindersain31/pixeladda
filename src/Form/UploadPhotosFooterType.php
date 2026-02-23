<?php

namespace App\Form;

use App\Entity\CommunityUploads;
use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UploadPhotosFooterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('photoFile', VichImageType::class, [
            'label' => 'Photo Image',
            'image_uri' => false,
            'required' => true,
            'allow_delete' => false,
            'constraints' => [
                new Constraints\File([
                    'maxSize' => '50M',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/gif',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid image file (JPEG, GIF, PNG) of up to 50MB.',
                    'maxSizeMessage' => 'The file is too large. Maximum allowed size is 50MB.',
                ]),
            ]
        ]);

        $builder->add('comment', TextareaType::class, [
            'label' => 'Enter Your Comment',
            'required' => true,
            'attr' => [
                'placeholder' => 'Enter Your Comment',
                'class' => 'mt-0',
            ],
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please enter Comment',
                ]),
                new Constraints\Length([
                    'min' => 2,
                    'minMessage' => 'Your Comment must be at least {{ limit }} characters long',
                    'maxMessage' => 'Your Comment cannot be longer than {{ limit }} characters',
                    'max' => 255,
                ])
            ]
        ]);

        $builder->add('g-recaptcha-response', HiddenType::class, [
            'mapped' => false,
        ]);
        $builder->add('recaptcha', ReCaptchaType::class, [
            'mapped' => false
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit',
            'attr' => [
                'class' => 'btn save-photo-btn'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommunityUploads::class
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}
