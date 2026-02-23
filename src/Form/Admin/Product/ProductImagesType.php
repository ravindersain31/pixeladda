<?php

namespace App\Form\Admin\Product;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Helper\VichS3Helper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;


class ProductImagesType extends AbstractType
{
    public function __construct(private readonly VichS3Helper $vichS3Helper, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('imageFile', VichImageType::class, [
            'label' => 'Product Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'imageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        /** @var Product $product */
        $product = $options['data'];
        $productFiles = $product->getProductImages();

        $builder->add('productImages', LiveCollectionType::class, [
            'label' => 'Product Images',
            'entry_type' => ProductImageType::class,
            'data' => $productFiles,
            'button_add_options' => [
                'label' => 'Add File',
                'attr' => [
                    'class' => 'btn btn-dark btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger btn-sm',
                ],
            ],
            'entry_options' => [
                'label' => false,
                'row_attr' => ['class' => 'mb-3 me-3 w-100']
            ],
            'row_attr' => ['class' => 'd-none'],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit Changes',
            'row_attr' => ['class' => 'text-center'],
            'attr' => [
                'class' => 'btn-dark mt-3',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
