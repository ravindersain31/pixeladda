<?php

namespace App\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class CustomOrderSampleVariantsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $editData = $options['editData'] ?? [];
        $item = $editData ? end($editData) : [];

        $width = isset($item['width']) ? $item['width'] : null;
        $height = isset($item['height']) ? $item['height'] : null;
        $quantity = isset($item['quantity']) ? $item['quantity'] : null;

        $builder
            ->add('width', Type\IntegerType::class, [
                'label' => 'Enter Width (in.)',
                'required' => false,
                'data' => $width,
                'attr' => [
                    'placeholder' => 'Enter Width',
                    'inputmode' => 'numeric',
                    'data-numeric-input' => true,
                    'class' => 'disable-scroll',
                    'data-disable-zero' => true
                ],
                'constraints' => [
                    new Constraints\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Please Enter valid Width and Height.',
                    ]),
                ]
            ])
            ->add('height', Type\IntegerType::class, [
                'label' => 'Enter Height (in.)',
                'required' => false,
                'data' => $height,
                'attr' => [
                    'placeholder' => 'Enter Height',
                    'inputmode' => 'numeric',
                    'data-numeric-input' => true,
                    'class' => 'disable-scroll',
                    'data-disable-zero' => true
                ],
                'constraints' => [
                    new Constraints\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Please Enter valid Width and Height.',
                    ]),
                ]
            ])
            ->add('quantity', Type\IntegerType::class, [
                'label' => 'Bulk Discounts!',
                'required' => false,
                'data' => $quantity,
                'attr' => [
                    'placeholder' => 'Enter Qty',
                    'inputmode' => 'numeric',
                    'data-numeric-input' => true,
                    'class' => 'disable-scroll',
                    'data-disable-zero' => true,
                    'data-scroll' => true
                ],
                'constraints' => [
                    new Constraints\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Please Enter valid Quantity.',
                    ]),
                    new Constraints\Range([
                        'min' => 1,
                        'max' => 3,
                        'notInRangeMessage' => 'Enter quantity between {{ min }} and {{ max }}.'
                    ])
                ]
            ]);

            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                $width = $data['width'];
                $height = $data['height'];

                if (($width > 96) || ($height > 96) || ($width > 48 && $height > 48)) {
                    if ($width > 96) {
                        $form->get('width')->addError(new FormError('The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.'));
                    }
                    if ($height > 96) {
                        $form->get('height')->addError(new FormError('The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.'));
                    }
                    if ($width > 48 && $height > 48) {
                        $form->get('width')->addError(new FormError('The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.'));
                        $form->get('height')->addError(new FormError('The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.'));
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
        $resolver->setRequired('editData');
    }
}
