<?php

namespace App\Form\Admin\Product;

use App\Constant\Editor\Addons;
use App\Entity\Product;
use App\Helper\VichS3Helper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductVariantType extends AbstractType
{
    public function __construct(
        private readonly VichS3Helper $vichS3Helper,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Product $product */
        $variants = $options['variants'];
        $product = $options['product'];
        $variantSizes = $product->getProductType()->getDefaultVariants();
        $customVariants = $product->getProductType()->getCustomVariants();
        $choices = [
            'Default Variants' => array_combine($variantSizes, $variantSizes),
            'Custom Variants'  => array_combine($customVariants, $customVariants),
        ];

        if($product->getSku() == 'WIRE-STAKE'){
            $choices = array_combine(Addons::getFrameTypes(), Addons::getFrameTypes());
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($product, $choices) {
            $form = $event->getForm();
            $form->add('name', Type\ChoiceType::class, [
                'label' => 'Size/Variant',
                // 'placeholder' => 'Choose Size/Variant',
                'help' => 'Please choose a size/variant',
                'help_html' => true,
                'required' => true,
                'choices' => $choices,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please choose a size/variant',
                    ]),
                ],
            ]);
        });

        $builder->add('name', Type\ChoiceType::class, [
            'label' => 'Size/Variant',
            // 'placeholder' => 'Choose Size/Variant',
            'help' => 'Please choose a size/variant',
            'help_html' => true,
            'required' => true,
            'choices' => $choices,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please choose a size/variant',
                ]),
            ],
        ]);

        $builder->add('label', Type\TextType::class, [
            'label' => 'Variant Label',
            'required' => false,
        ]);

        $builder->add('modalName', Type\TextType::class, [
            'label' => 'Internal Name/Number',
            'required' => false,
        ]);

        $builder->add('sortPosition', Type\IntegerType::class, [
            'label' => 'Sort Position',
            'required' => false,
        ]);

        $builder->add('imageFile', VichImageType::class, [
            'label' => 'Variant Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'imageFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('promoImageFile', VichImageType::class, [
            'label' => 'Promo Variant Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'promoImageFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('seoImageFile', VichImageType::class, [
            'label' => 'SEO/Ads Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'seoImageFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('displayImageFile', VichImageType::class, [
            'label' => 'Display Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'displayImageFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('templateLabel', Type\TextType::class, [
            'label' => 'Template Label',
            'required' => false,
        ]);

        $builder->add('templateFile', VichFileType::class, [
            'label' => 'Template',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'templateFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            // 'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('promoTemplateFile', VichFileType::class, [
            'label' => 'Promo Template',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'promoTemplateFile');
            },
            'label_attr' => ['class' => 'pt-0'],
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('isCustomSize', Type\CheckboxType::class, [
            'label' => 'Enable Custom Size',
            'help' => 'Tick/Untick if you want to enable Custom Size Option.',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'variants' => [],
            'product' => null,
        ]);

        $resolver->setAllowedTypes('variants', ['array']);
        $resolver->setAllowedTypes('product', ['null', Product::class]);

    }
}
