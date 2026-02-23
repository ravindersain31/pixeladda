<?php

namespace App\Form\Admin\Product;

use App\Entity\ProductImage;
use App\Helper\VichS3Helper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type;

class ProductImageType extends AbstractType
{
    public function __construct(private readonly VichS3Helper $vichS3Helper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('imageFile', VichImageType::class, [
            'label' => 'Product Image',
            'download_uri' => function (ProductImage $product) {
                return $this->vichS3Helper->asset($product, 'imageFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            'image_uri' => true,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('sortPosition', Type\IntegerType::class, [
                'label' => 'Position',
                'required' => true,
        ]);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductImage::class,
        ]);
    }
}
