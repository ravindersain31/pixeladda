<?php

namespace App\Form\Admin\Order\New;

use App\Entity\Order;
use App\Entity\ProductType;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderTagsEnum;
use App\Form\AddressType;
use App\Helper\ShippingChartHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class NewOrderType extends AbstractType
{
    public function __construct(private readonly ShippingChartHelper $shippingChartHelper, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderId', Type\TextType::class, [
            'label' => 'Order ID',
            'required' => false,
            'help' => 'Enter the order ID for this order, or leave blank to generate one automatically.',
            'row_attr' => ['class' => 'mb-0'],
            'constraints' => [
                new Constraints\Length(max: 40),
                new Constraints\Regex(pattern: '/^[a-zA-Z0-9-]+$/', message: 'Order ID can only contain letters, numbers, and hyphens.')
            ]
        ]);

        $builder->add('parent', OrderIDAutocomplete::class, [
            'label' => 'Parent Order ID',
            'required' => false,
            'help' => 'Enter the parent Order ID for this order, or leave blank if this is a standalone order.',
            'row_attr' => ['class' => 'mb-0'],
        ]);

        $builder->add('orderChannel', Type\ChoiceType::class, [
            'label' => 'Order Type',
            'required' => false,
            'placeholder' => '-- Select --',
            'choices' => [
                OrderChannelEnum::REPLACEMENT->label() => OrderChannelEnum::REPLACEMENT,
                OrderChannelEnum::SM3->label() => OrderChannelEnum::SM3,
                OrderChannelEnum::SALES->label() => OrderChannelEnum::SALES,
            ],
            'help' => 'Select the order type for this order.',
            'row_attr' => ['class' => 'mb-0'],
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select an order type.')
            ]
        ]);

        $builder->add('orderTag', Type\ChoiceType::class, [
            'label' => 'Order Tag(s)',
            'choices' => array_flip(OrderTagsEnum::LABELS),
            'required' => false,
            'multiple' => true,
            'autocomplete' => true,
            'mapped' => false,
            'attr' => [
                'class' => 'form-select-sm'
            ]
        ]);

        $builder->add('billingAddress', AddressType::class, [
            'label_attr' => ['class' => 'p-0'],
        ]);
        $builder->add('shippingAddress', AddressType::class, [
            'label_attr' => ['class' => 'p-0'],
        ]);

        $builder->add('items', Type\CollectionType::class, [
            'entry_type' => NewOrderItemType::class,
            'label' => false,
            'mapped' => false,
            'entry_options' => [
                'label' => false,
                'attr' => ['class' => 'row flex-fill me-1 order-item']
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'data' => [[]],
        ]);

        $builder->add('additionalNotes', Type\TextareaType::class, [
            'label' => 'Additional Notes',
            'mapped' => false,
            'required' => false,
            'row_attr' => ['class' => 'mb-0']
        ]);

        $productType = $this->entityManager->getRepository(ProductType::class)->findOneBy(['slug' => 'yard-sign']);
        $shippingChart = $this->shippingChartHelper->build($productType->getShipping());
        $shippingDates = $this->shippingChartHelper->getShippingByQuantity(1, $shippingChart);
        $shippingDatesChoices = [];
        foreach ($shippingDates as $date) {
            $formattedDate = (new \DateTimeImmutable($date['date']))->format('M d, Y');
            $label = $formattedDate;
            if ($date['free']) {
                if ($date['discount'] > 0) {
                    $label .= ' (+' . $date['day'] . ' Free, +5%)';
                } else {
                    $label .= ' (+' . $date['day'] . ' Free)';
                }
            } else {
                $label .= ' (+' . $date['day'] . ')';
            }
            $shippingDatesChoices[$label] = $date['day'];
        }
        $freeShipping = end($shippingDates);
        $builder->add('shippingDate', Type\ChoiceType::class, [
            'label' => 'Shipping Date',
            'choices' => $shippingDatesChoices,
            'mapped' => false,
            'required' => true,
            'expanded' => true,
            'placeholder' => '-- Select --',
            'row_attr' => ['class' => 'mb-0'],
            'attr' => ['class' => 'd-flex justify-content-between'],
            'data' => $freeShipping['day'],
            'help' => 'Select the shipping date for this order, and shipping fees will be calculated based on the selected date.',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a shipping date.')
            ]
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Create Order',
            'attr' => ['class' => 'btn btn-primary']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
