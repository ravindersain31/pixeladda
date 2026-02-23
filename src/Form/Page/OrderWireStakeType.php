<?php

namespace App\Form\Page;

use App\Constant\Editor\Addons;
use App\Entity\Product;
use App\Helper\OrderWireStakeHelper;
use App\Twig\LightCartProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class OrderWireStakeType extends AbstractType
{
    public function __construct(
        private readonly OrderWireStakeHelper $orderWireStakeHelper,
        private readonly LightCartProvider $lightCartProvider
    ){
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $item = $options['editData'] ? end($options['editData']['items']) ?? null : null;
        $variantsData = [];
        $totalCartQuantity = $this->lightCartProvider->getTotalQuantity();
        $currentItemQuantity = isset($item['quantity']) ? $item['quantity'] : 0;
        /** @var array|Product $product */
        $product = $options['product'];
        $editShipping = null;
        $isBlindShipping = false;
        if ($item) {
            $editShipping = $item['data']['shipping'] ?? null;
            $editDeliveryMethod = $item['data']['deliveryMethod'] ?? null;
            $isBlindShipping = $item['data']['isBlindShipping'] ?? false;

            $variantsData = [
                Addons::filterFrameTypeLabel($item['data']['name']) => $item['quantity'],
            ];
        }
        $builder->add('variants', OrderWireStakeVariantsType::class, [
            'data' => $variantsData,
            'variants' => $options['variants'],
            'editData' => $options['editData'],
            'product' => $product,
        ]);

        $shipping = $this->orderWireStakeHelper->updateShipping($totalCartQuantity ?? 0);

        $keys = array_values($shipping['options']);
        $fourthValue = $keys[3];

        $builder->add('shipping', Type\ChoiceType::class, [
            'label' => 'Select Shipping',
            'block_prefix' => 'shipping_date',
            'choices' => $shipping['options'],
            'expanded' => true,
            'data' => $editShipping ? $editShipping['day'] : $fourthValue,
            'choice_attr' => function ($choice, $key, $value) use ($shipping) {
                return [
                    'class' => 'd-none delivery-dates',
                    'data-day' => $value,
                    'data-date' => $key,
                    'data-price' => $shipping['data'][$key]['price'],
                    'data-discount' => $shipping['data'][$key]['discount'],
                ];
            },
        ]);

        $builder->add('pickup', Type\CheckboxType::class, [
            'label' => 'Request Pickup',
            'label_attr' => [
                'class' => 'd-none',
            ],
            'data' => isset($editDeliveryMethod) && $editDeliveryMethod['key'] == 'REQUEST_PICKUP' ? true : false,
            'attr' => [
                'class' => 'border d-block mt-1 m-auto btn btn-primary',
            ],
        ]);

        $builder->add('isBlindShipping', Type\CheckboxType::class, [
            'label' => 'Request Blind Shipping',
            'label_attr' => [
                'class' => 'd-none',
            ],
            'data' => $isBlindShipping,
            'attr' => [
                'class' => 'border d-block mt-1 m-auto btn btn-primary',
            ],
        ]);

        $builder->add('comment', Type\TextareaType::class, [
            'label' => 'Add Comment',
            'data' => isset($item['data']['additionalNote']) ? $item['data']['additionalNote'] : null,
            'row_attr'=> [
                'class' => 'm-0',
            ],
            'attr' => [
                'placeholder' => 'Add additional instructions. Your comments will be seen when we receive your order.',
                'class' => 'form-control border'
            ],
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($totalCartQuantity, $currentItemQuantity) {
            $data = $event->getData();
            $form = $event->getForm();

            $quantity = array_sum(array_map('intval', $data['variants'] ?? []));
            $shipping = $this->orderWireStakeHelper->updateShipping(($quantity + $totalCartQuantity) - $currentItemQuantity);

            $form->add('shipping', Type\ChoiceType::class, [
                'label' => 'Select Shipping',
                'block_prefix' => 'shipping_date',
                'choices' => $shipping['options'],
                'expanded' => true,
                'choice_attr' => function ($choice, $key, $value) use ($shipping) {
                    return [
                        'class' => 'd-none',
                        'data-day' => $value,
                        'data-date' => $key,
                        'data-price' => $shipping['data'][$key]['price'],
                        'data-discount' => $shipping['data'][$key]['discount'],
                    ];
                },
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);

        $resolver->setRequired('variants');
        $resolver->setRequired('editData');
        $resolver->setRequired('product');
    }
}
