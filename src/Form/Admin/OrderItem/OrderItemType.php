<?php

namespace App\Form\Admin\OrderItem;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\Admin\Order\New\AddonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OrderItemType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OrderItem $orderItem */
        $orderItem = $options['data'];

        if ($orderItem && $orderItem->getOrder()->isIsManual()) {
            $customSize = $orderItem->getMetaDataKey('customSize');
            $templateSize = $customSize['templateSize'];

            $builder->add('width', Type\IntegerType::class, [
                'label' => 'Width (in)',
                'mapped' => false,
                'data' => $templateSize['width'] ?? null,
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\GreaterThanOrEqual(1, message: 'Width must be greater than or equal to 1'),
                ],
            ]);
            $builder->add('height', Type\IntegerType::class, [
                'label' => 'Height (in)',
                'mapped' => false,
                'data' => $templateSize['height'] ?? null,
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\GreaterThanOrEqual(1, message: 'Height must be greater than or equal to 1'),
                ],
            ]);
        }

        $builder->add('quantity', Type\NumberType::class, [
            'label' => 'Quantity',
            'attr' => [
                'placeholder' => 'Quantity',
                'min' => 0,
                'step' => 0.01,
                'max' => 1000000,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\Positive(),
                new Constraints\GreaterThan(0),
            ],
        ]);

        $builder->add('unitAmount', Type\NumberType::class, [
            'label' => 'Price',
            'attr' => [
                'placeholder' => 'Price',
                'min' => 0,
                'step' => 0.01,
                'max' => 1000000,
            ],
            'constraints' => [
                new Constraints\NotBlank(),
                new Constraints\GreaterThanOrEqual(0),
            ],
        ]);

        $addOns = $orderItem->getAddOns() ?? [];

        $builder->add('addons', AddonsType::class, [
            'label' => false,
            'mapped' => false,
            'data' => $addOns,
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'calculateTotalAmount']);
    }

    public function calculateTotalAmount(FormEvent $event): void
    {
        $data = $event->getData(); // This is an array, not an OrderItem instance
        $form = $event->getForm();

        /** @var OrderItem $orderItem */
        $orderItem = $form->getData(); // Get the actual OrderItem entity

        if (!$orderItem instanceof OrderItem || !isset($data['quantity']) || !isset($data['unitAmount'])) {
            return;
        }

        $quantity = (float) $data['quantity'];
        $unitAmount = (float) $data['unitAmount'];

        // Calculate total amount
        $totalAmount = $quantity * $unitAmount;

        // Update the OrderItem entity
        $orderItem->setPrice($unitAmount);
        $orderItem->setTotalAmount($totalAmount);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
