<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['data'];

        $builder->add('shippingAddress', AddressType::class, [
            'label' => 'Shipping Address',
            'label_html' => true,
            'data' => $order->getShippingAddress(),
        ]);
        $builder->add('billingAddress', AddressType::class, [
            'label' => 'Billing Address',
            'label_html' => true,
            'data' => $order->getBillingAddress(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class
        ]);
    }
}
