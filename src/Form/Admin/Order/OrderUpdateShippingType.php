<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Entity\ProductType;
use App\Enum\OrderChannelEnum;
use App\Form\AddressType;
use App\Helper\ShippingChartHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class OrderUpdateShippingType extends AbstractType
{
    public function __construct(private readonly ShippingChartHelper $shippingChartHelper, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $productType = $this->entityManager->getRepository(ProductType::class)->findOneBy(['slug' => 'yard-sign']);
        $shippingChart = $this->shippingChartHelper->build($productType->getShipping());
        $shippingDates = $this->shippingChartHelper->getShippingByQuantity(1, $shippingChart);
        $shippingDatesChoices = [];
        $selectedShippingDate = null;

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

            if ($options['data']->getDeliveryDate() && $date['date'] == $options['data']->getDeliveryDate()->format('Y-m-d')) {
                $selectedShippingDate = $date['day'];
            }
        }

        if (!$selectedShippingDate) {
            $selectedShippingDate = end($shippingDates)['day'];
        }

        $builder->add('shippingDate', Type\ChoiceType::class, [
            'label' => 'Shipping Date',
            'choices' => $shippingDatesChoices,
            'mapped' => false,
            'required' => true,
            'expanded' => true,
            'placeholder' => '-- Select --',
            'row_attr' => ['class' => 'mb-0 px-4'],
            'attr' => ['class' => 'row'],
            'data' => $selectedShippingDate,
            'help' => 'Select the shipping date for this order, and shipping fees will be calculated based on the selected date.',
            'constraints' => [
                new Constraints\NotBlank(message: 'Please select a shipping date.')
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
