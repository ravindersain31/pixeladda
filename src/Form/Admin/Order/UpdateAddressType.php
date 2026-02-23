<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Form\AddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class UpdateAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['order'];
        $addressType = $options['addressType'];
        $address = $order->getAddress($addressType);

        $label = ucfirst($addressType);
        if (str_contains($addressType, 'shipping')) {
            $label = '<b>Shipping</b> Address';
        } elseif (str_contains($addressType, 'billing')) {
            $label = '<b>Billing</b> Address';
        }

        $builder->add($addressType, AddressType::class, [
            'label' => $label,
            'label_html' => true,
            'label_attr' => ['class' => 'p-0'],
            'data' => $address,
        ]);

        if (str_contains($addressType, 'shipping')) {
            $builder->add('textUpdates', Type\CheckboxType::class, [
                'label' => 'Mobile Text Updates',
                'required' => false,
                'data' => $order->isTextUpdates(),
            ]);

            $builder->add('textUpdatesNumber', Type\TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Enter phone number for text updates'],
                'data' => $order->getTextUpdatesNumber() ?? '',
                'constraints' => [
                    new Constraints\Regex(
                        pattern: '/^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$/',
                        message: 'Please enter a valid phone number.'
                    ),
                ],
            ]);

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                $isTextUpdate = $data['textUpdates'] ?? false;

                if ($isTextUpdate) {
                    $form->add('textUpdatesNumber', Type\TextType::class, [
                        'label' => false,
                        'required' => true,
                        'attr' => ['placeholder' => 'Enter phone number for text updates'],
                        'constraints' => [
                            new Constraints\NotBlank(['message' => 'Phone number is required for text updates.']),
                            new Constraints\Regex(
                                pattern: '/^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$/',
                                message: 'Please enter a valid phone number.'
                            ),
                        ],
                    ]);
                }
            });

        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('order');
        $resolver->setRequired('addressType');
    }
}
