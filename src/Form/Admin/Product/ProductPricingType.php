<?php

namespace App\Form\Admin\Product;

use App\Entity\Product;
use App\Form\Admin\Product\Fields\PricingFields;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class ProductPricingType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Product $product */
        $product = $options['product'];
        $store = $product->getStore();
        $domains = $store->getStoreDomains();
        $variants = $product->getVariants();

        foreach ($variants as $variant) {
            $pricing = $product->getPricing();
            $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event) use ($variant) {
                $form = $event->getForm();
                $data = $event->getData();
                if ($variant instanceof Product && $form->isValid()) {
                    $variant->setPricing($data['pricing_' . $variant->getName()]);
                    $this->entityManager->persist($variant);
                    $this->entityManager->flush();
                }
            });
            $builder->add('pricing_' . $variant->getName(), LiveCollectionType::class, [
                'label' => false,
                'data' => $pricing['pricing_' . $variant->getName()] ?? [],
//                'label' => 'Pricing for Variant: ' . $variant,
                'entry_type' => PricingFields::class,
                'required' => true,
                'allow_add' => true,
                'label_attr' => ['class' => 'py-1 text-dark'],
                'button_add_options' => [
                    'label' => 'Add Price',
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
                    'domains' => $domains,
                ],
                'attr' => ['class' => 'row']
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('product');
        $resolver->setDefaults([
        ]);
    }

}
