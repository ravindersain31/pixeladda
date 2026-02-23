<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Enum\OrderTagsEnum;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class OrderTagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $existingTags = $options['metaData']['tags'] ?? [];
        $deliveryMethodKey = $options['metaData']['deliveryMethod']['key'] ?? '';
        $isFreeFreight = $options['metaData']['isFreeFreight'] ?? false;
        $isBlindShipping = $options['metaData']['isBlindShipping'] ?? false;
        $isSaturdayDelivery = $options['metaData']['isSaturdayDelivery'] ?? false;

        $selectedTags = array_keys(array_filter($existingTags, fn($tag) => $tag['active'] ?? false));

        if ($deliveryMethodKey === 'REQUEST_PICKUP' || in_array('REQUEST_PICKUP', $selectedTags, true)) {
            if (!in_array('REQUEST_PICKUP', $selectedTags, true)) {
                $selectedTags[] = 'REQUEST_PICKUP';
            }
        }

        if ($isFreeFreight && !in_array('FREIGHT', $selectedTags, true)) {
            $selectedTags[] = 'FREIGHT';
        }

        if ($isBlindShipping && !in_array('BLIND_SHIPPING', $selectedTags, true)) {
            $selectedTags[] = 'BLIND_SHIPPING';
        }

        if ($isSaturdayDelivery && !in_array('SATURDAY_DELIVERY', $selectedTags, true)) {
            $selectedTags[] = 'SATURDAY_DELIVERY';
        }

        $builder->add('orderTag', ChoiceType::class, [
            'label' => 'Order Tag(s)',
            'choices' => array_flip(OrderTagsEnum::LABELS),
            'required' => false,
            'multiple' => true,
            'autocomplete' => true,
            'mapped' => false,
            'data' => $selectedTags,
            'placeholder' => '-- Select tags --',
            'attr' => [
                'class' => 'form-select-sm',
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Save',
            'attr' => ['class' => 'btn btn-primary']
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $order = $event->getData();

            if ($form->isSubmitted() && $form->isValid()) {
                $orderTags = $form->get('orderTag')->getData();
                $this->buildOrderTags($order, $orderTags);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'metaData' => [],
        ]);
    }

    public function buildOrderTags(Order $order, array $orderTags): void
    {
        $customTags = [];
        foreach (OrderTagsEnum::ALL_TAGS as $key => $name) {
            $customTags[$key] = [
                'name' => $name,
                'active' => in_array($key, $orderTags, true),
            ];
        }

        $order->setMetaDataKey('tags', $customTags);
        $order->setIsFreightRequired($customTags[OrderTagsEnum::FREIGHT]['active']);

        $requestPickup = in_array('REQUEST_PICKUP', $orderTags);
        $blindShipping = in_array('BLIND_SHIPPING', $orderTags);
        $isSaturdayDelivery = in_array('SATURDAY_DELIVERY', $orderTags);
        $freight = in_array('FREIGHT', $orderTags);

        $isSuperRush = in_array('SUPER_RUSH', $orderTags);

        $order->setIsSuperRush($isSuperRush);

        $order->setMetaDataKey('isFreeFreight', $freight);
        $order->setMetaDataKey('isBlindShipping', $blindShipping);
        $order->setMetaDataKey('isSaturdayDelivery', $isSaturdayDelivery);
        $order->setMetaDataKey('deliveryMethod', [
            "key" => $requestPickup ? "REQUEST_PICKUP" : "DELIVERY",
            "type" => "percentage",
            "label" => $requestPickup ? "Request Pickup" : "Delivery",
            "discount" => 0
        ]);
    }
}
