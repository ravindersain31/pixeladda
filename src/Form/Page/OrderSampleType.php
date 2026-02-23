<?php

namespace App\Form\Page;

use App\Form\Page\CustomOrderSampleVariantsType;
use App\Helper\OrderSampleHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use App\Constant\Editor\Addons;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class OrderSampleType extends AbstractType
{
    public function __construct(private readonly OrderSampleHelper $orderSampleHelper, private readonly Addons $addons)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $item = $options['editData'] ? end($options['editData']['items']) ?? null : null;
        $variantsData = [];
        $customData = [[
            'width' => null,
            'height' => null,
            'quantity' => null,
        ]];
        $editShipping = null;
        $isBlindShipping = false;
        $isFreeFreight = false;
        $sideData = Addons::SIDES_SINGLE;
        $shapeData = Addons::SHAPE_SQUARE;
        if ($item) {
            $editShipping = $item['data']['shipping'] ?? null;
            $isBlindShipping = $item['data']['isBlindShipping'] ?? false;
            $isFreeFreight = $item['data']['isFreeFreight'] ?? false;
            $editDeliveryMethod = $item['data']['deliveryMethod'] ?? null;
            if (!isset($item['data']['customSize'])) {
                $variantsData = [
                    $item['data']['name'] => $item['quantity']
                ];
            }
            if(isset($item['data']['customSize']) && $item['data']['isSample']){
                $customData = [[
                    'width' => $item['data']['templateSize']['width'],
                    'height' => $item['data']['templateSize']['height'],
                    'quantity' => $item['quantity']
                ]];
            }

            if(isset($item['data']['addons']['sides']) && $item['data']['addons']['sides']['key'] === Addons::SIDES_DOUBLE){
                $sideData = Addons::SIDES_DOUBLE;
            }

            if(isset($item['data']['addons']['shape']) && $item['data']['addons']['shape']['key'] === Addons::SHAPE_CUSTOM){
                $shapeData = Addons::SHAPE_CUSTOM;
            }
        }

        $sidesChoices = $this->addons->ChooseYourSides();
        $sidesData = [];

        foreach ($sidesChoices as $key => $value) {
            $sidesData[$value] = $this->addons->getSidesData($value);
        }

        $shapesChoices = $this->addons->getSampleShapes();
        $shapesData = [];

        foreach ($shapesChoices as $key => $value) {
            $shapesData[$value] = $this->addons->getSampleShapesData($value);
        }

        $builder->add('variants', OrderSampleVariantsType::class, [
            'data' => $variantsData,
            'variants' => $options['variants'],
            'editData' => $options['editData'],
        ]);

        $builder->add('customSize', LiveCollectionType::class, [
            'label' => 'Enter Custom Size',
            'data' => $customData,
            'entry_type' => CustomOrderSampleVariantsType::class,
            'allow_add' => true,
            'entry_options' => [
                'editData' => $customData,
            ],
            'label_attr' => ['class' => 'text-center bold fs-6 fw-500'],
            'button_add_options' => [
                'label' => '+ Add Size',
                'attr' => [
                    'class' => 'btn addSize-btn btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
               'label' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
                'attr' => [
                    'class' => 'btn ysp-bg-purple rounded-circle text-white btn-sm',
                ],
                'label_html' => true,
            ],
            'row_attr' => ['class' => 'd-none'],
            'attr' => ['class' => 'row']
        ]);

        $shipping = $this->orderSampleHelper->getShippingOptions();

        $builder->add('shipping', Type\ChoiceType::class, [
            'label' => 'Select Shipping',
            'block_prefix' => 'shipping_date',
            'choices' => $shipping['options'],
            'expanded' => true,
            'data' => $editShipping ? $editShipping['day'] : end($shipping['options']),
            'choice_attr' => function ($choice, $key, $value) use ($shipping) {
                return [
                    'class' => 'd-none',
                    'data-day' => $value,
                    'data-date' => $key,
                    'data-price' => $shipping['data'][$key]['price'],
                ];
            },
        ]);

        $builder->add('chooseYourSides', Type\ChoiceType::class, [
            'label' => 'Choose Your Sides',
            'choices' => $sidesChoices,
            'expanded' => true,
            'multiple' => false,
            'attr' => [
                'class' => 'border d-block mt-1 m-auto btn btn-primary',
            ],
            'data' => $sideData,
            'choice_attr' => function ($choice, $key, $value) use ($sidesData) {
                $side = $sidesData[$value];
                return [
                    'data-sides' => $choice,
                    'data-amount' => $side['amount'],
                    'data-type' => $side['type'],
                    'data-key' => $side['key'],
                    'data-img' => $side['img'],
                    'data-displayAmount' => $side['displayAmount'],
                    'data-displayText' => $side['displayText'],
                ];
            }
        ]);

        $builder->add('chooseYourShape', Type\ChoiceType::class, [
            'label' => 'Choose Your Shapes',
            'choices' => $shapesChoices,
            'expanded' => true,
            'multiple' => false,
            'attr' => [
                'class' => 'border d-block mt-1 m-auto btn btn-primary',
            ],
            'data' => $shapeData,
            'choice_attr' => function ($choice, $key, $value) use ($shapesData) {
                $shape = $shapesData[$value];
                return [
                    'data-sides' => $choice,
                    'data-amount' => $shape['amount'],
                    'data-type' => $shape['type'],
                    'data-key' => $shape['key'],
                    'data-img' => $shape['img'],
                    'data-displayAmount' => $shape['displayAmount'],
                    'data-displayText' => $shape['displayText'],
                ];
            }
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

        $builder->add('isFreeFreight', Type\CheckboxType::class, [
            'label' => 'Free Freight',
            'label_attr' => [
                'class' => 'd-none',
            ],
            'data' => $isFreeFreight,
            'attr' => [
                'class' => 'border d-block mt-1 m-auto btn btn-primary',
            ],
        ]);

        $builder->add('isFreeFreightModal', Type\CheckboxType::class, [
            'label' => 'Free Freight',
            'label_attr' => [
                'class' => 'd-none',
            ],
            'data' => $isFreeFreight,
            'attr' => [
                'class' => 'border d-block mt-1 m-auto btn btn-primary',
            ],
        ]);

        $builder->add('comment', Type\TextareaType::class, [
            'label' => 'Add Comment',
            'data' => isset($item['data']['additionalNote']) ? $item['data']['additionalNote'] : null,
            'attr' => [
                'placeholder' => 'Add additional instruction',
                'class' => 'form-control border'
            ],
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $form->getData();

            $hasQty = false;
            foreach ($data['variants'] as $variant) {
                if ($variant !== null) {
                    $hasQty = true;
                    break;
                }
            }

            foreach ($data['customSize'] as $index => $size) {
                $hasQty = isset($size['quantity']) && $size['quantity'] !== null;

                $missingWidthOrHeight = empty($size['width']) || empty($size['height']);

                if ($missingWidthOrHeight) {
                    if ($hasQty) {
                        $form->get('customSize')->get($index)->get('width')->addError(new FormError('Please Enter Width and Height.'));
                        $form->get('customSize')->get($index)->get('height')->addError(new FormError('Please Enter Width and Height.'));
                    }
                }
                else if ($hasQty && $missingWidthOrHeight) {
                    $form->get('customSize')->get($index)->get('width')->addError(new FormError('Please Enter Width and Height.'));
                    $form->get('customSize')->get($index)->get('height')->addError(new FormError('Please Enter Width and Height.'));
                }
                else if (!$hasQty && ($size['width'] !== null || $size['height'] !== null)) {
                    $form->get('customSize')->get($index)->get('quantity')->addError(new FormError('Please Enter Quantity.'));
                }
            }

        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);

        $resolver->setRequired('variants');
        $resolver->setRequired('editData');
    }
}
