<?php

namespace App\Form\Admin\Product;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Store;
use App\Entity\ProductType as ProductTypeEntity;
use App\Form\Admin\Product\Fields\FrameTypeField;
use App\Form\Types\ProductSeoMetaType;
use App\Helper\VichS3Helper;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductType extends AbstractType
{
    private FormFactoryInterface $factory;

    private array $dependencies = [];

    private bool $prePackedFieldsPresent = false;

    public function __construct(private readonly VichS3Helper $vichS3Helper)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['data'];
        if ($product instanceof Product) {
            $this->dependencies['store'] = $product->getStore();
        }
        $this->factory = $builder->getFormFactory();

        $builder->add('name', Type\TextType::class, [
            'label' => 'Product Title',
        ]);
        $builder->add('modalName', Type\TextType::class, [
            'label' => 'Internal Name/Number',
            'required' => false,
        ]);
        $builder->add('store', EntityType::class, [
            'class' => Store::class,
            'placeholder' => '-- Select Store --',
            'query_builder' => function (EntityRepository $er) use ($product) {
                $qb = $er->createQueryBuilder('S');
                $qb->where($qb->expr()->eq('S.isEnabled', ':isEnabled'));
                $qb->setParameter('isEnabled', true);
                return $qb->orderBy('S.name', 'ASC');
            }
        ]);

        $builder->add('imageFile', VichImageType::class, [
            'label' => 'Product Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'imageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('promoImageFile', VichImageType::class, [
            'label' => 'Promo Product Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'promoImageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('seoImageFile', VichImageType::class, [
            'label' => 'SEO/Ads Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'seoImageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('displayImageFile', VichImageType::class, [
            'label' => 'Display Image',
            'download_uri' => function (Product $product) {
                return $this->vichS3Helper->asset($product, 'displayImageFile');
            },
            'image_uri' => false,
            'required' => false,
            'allow_delete' => false,
        ]);

        $builder->add('hasVariant', Type\CheckboxType::class, [
            'label' => 'Enable Variant',
            'help' => 'Tick/Untick if you want to enable variant step.',
            'required' => false,
        ]);

        $builder->add('isEnabled', Type\CheckboxType::class, [
            'label' => 'Enable Product',
            'help' => 'Tick/Untick if you want to enable product',
            'row_attr' => [
                'class' => 'd-none',
            ],
            'required' => false,
        ]);

        $builder->add('seoMeta', ProductSeoMetaType::class);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmitPrePackedValidation']);

        $builder->get('store')->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setDependencies']);
        $builder->get('store')->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmitStore']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        $this->addPrimaryCategoryField($event->getForm(), $data?->getStore());
        $this->addSubCategoriesField($event->getForm(), $data?->getStore());
        $this->addTypeField($event->getForm(), $data?->getStore());
        if ($data && $data->getProductType() && $data->getProductType()->getSlug() === 'yard-letters') {
            $this->addPrePackedFields($event->getForm(), $data);
        }
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $this->dependencies = [];
    }

    public function setDependencies(FormEvent $event): void
    {
        $this->dependencies[$event->getForm()->getName()] = $event->getForm()->getData();
    }

    public function onPostSubmitStore(FormEvent $event): void
    {
        $this->addPrimaryCategoryField($event->getForm()->getParent(), $this->dependencies['store']);
        $this->addSubCategoriesField($event->getForm()->getParent(), $this->dependencies['store']);
        $this->addTypeField($event->getForm()->getParent(), $this->dependencies['store']);
    }

    public function onPostSubmitProductType(FormEvent $event): void
    {
        $form = $event->getForm()->getParent();
        $productType = $event->getForm()->getData();
        $product = $form->getData();
        if ($productType && $productType->getSlug() === 'yard-letters') {
            $this->addPrePackedFields($form, $product);
        } else {
            $this->prePackedFieldsPresent = false;
            $form->remove('totalSigns');
            $form->remove('frameTypes');
        }
    }

    public function onPostSubmitPrePackedValidation(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $form->getData();
        if(!$this->prePackedFieldsPresent) {
            return;
        }
        $totalSigns = $form->get('totalSigns')->getData();
        $frameTypes = [];
        foreach ($form->get('frameTypes')->all() as $frameField) {
            $frameTypeName = $frameField->getName();
            $frameTypes[$frameTypeName] = $frameField->getData();
        }
        if ($totalSigns !== null && is_array($frameTypes)) {
            $totalFrameQty = array_sum($frameTypes);

            if ($totalFrameQty > $totalSigns) {
                $form->get('totalSigns')->addError(
                    new FormError('The total frame quantities cannot exceed the total number of signs.')
                );
            }
            $data->setProductMetaDataKey('totalSigns', $form->get('totalSigns')->getData());
            $data->setProductMetaDataKey('frameTypes', $frameTypes);
        }
    }

    public function addSubCategoriesField(FormInterface $form, ?Store $store): void
    {
        $categories = $this->factory
            ->createNamedBuilder('categories', EntityType::class, null, [
                'label' => 'Sub Categories',
                'class' => Category::class,
                'placeholder' => '-- Select Category --',
                'query_builder' => function (EntityRepository $er) use ($store) {
                    $qb = $er->createQueryBuilder('C');
                    return $qb->andWhere($qb->expr()->eq('C.store', ':store'))
                        // ->andWhere($qb->expr()->eq('C.isEnabled', ':isEnabled'))
                        // ->andWhere($qb->expr()->eq('C.displayInMenu', ':isEnabled'))
                        ->setParameter('store', $store)
                        // ->setParameter('isEnabled', true)
                        ->orderBy('C.name', 'ASC');
                },
                'help' => null === $store ? 'Select a store first to choose the categories' : '',
                'auto_initialize' => false,
                'invalid_message' => false,
                'autocomplete' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setDependencies']);

        $form->add($categories->getForm());
    }

    public function addPrimaryCategoryField(FormInterface $form, ?Store $store): void
    {
        $categories = $this->factory
            ->createNamedBuilder('primaryCategory', EntityType::class, null, [
                'label' => 'Primary Category',
                'class' => Category::class,
                'placeholder' => '-- Select Category --',
                'query_builder' => function (EntityRepository $er) use ($store) {
                    $qb = $er->createQueryBuilder('C');
                    return $qb->andWhere($qb->expr()->eq('C.store', ':store'))
                        // ->andWhere($qb->expr()->eq('C.isEnabled', ':isEnabled'))
                        // ->andWhere($qb->expr()->eq('C.displayInMenu', ':isEnabled'))
                        ->setParameter('store', $store)
                        // ->setParameter('isEnabled', true)
                        ->orderBy('C.name', 'ASC');
                },
                'help' => null === $store ? 'Select a store first to choose the categories' : '',
                'auto_initialize' => false,
                'invalid_message' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setDependencies']);

        $form->add($categories->getForm());
    }

    public function addTypeField(FormInterface $form, ?Store $store): void
    {
        $productType = $this->factory
            ->createNamedBuilder('productType', EntityType::class, null, [
                'label' => 'Product Type',
                'class' => ProductTypeEntity::class,
                'query_builder' => function (EntityRepository $er) use ($store) {
                    $qb = $er->createQueryBuilder('P');
                    return $qb->andWhere($qb->expr()->eq('P.store', ':store'))
                        ->setParameter('store', $store)
                        ->orderBy('P.name', 'ASC');
                },
                'placeholder' => null === $store ? 'Select a store first' : ' -- Select Product Type -- ',
                'disabled' => null === $store,
                'auto_initialize' => false,
                'invalid_message' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setDependencies'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmitProductType']);

        $form->add($productType->getForm());
    }

    public function addPrePackedFields(FormInterface $form, Product $product): void
    {
        $this->prePackedFieldsPresent = true;
        $form->add('totalSigns', Type\IntegerType::class, [
            'label' => 'Total Signs',
            'required' => false,
            'mapped' => false,
            'attr' => ['placeholder' => 'Total Signs'],
            'data' => $product->getProductMetaDataKey('totalSigns') ?? $product->getProductMetaDataKey('totalSigns'),
            'constraints' => [
                new NotBlank([
                    'message' => 'Please specify the total number of signs.',
                ]),
            ],
        ]);

        $form->add('frameTypes', FrameTypeField::class, [
            'label' => 'Frame Quantities',
            'required' => false,
            'mapped' => false,
            'data' => $product->getProductMetaDataKey('frameTypes') ?? $product->getProductMetaDataKey('frameTypes'),
        ]);
    }
}
