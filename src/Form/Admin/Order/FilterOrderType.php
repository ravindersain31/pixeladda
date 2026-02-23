<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Service\OrderLogger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FilterOrderType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $orderStatusOptions = OrderStatusEnum::LABELS;

        $builder->add('search', TextType::class, [
            'label' => 'Search',
            'attr' => ['placeholder' => 'Search by Order Id / Customer Name'],
            'required' => false,
        ]);

        $builder->add('fromDate', TextType::class, [
            'label' => 'From',
            'required' => false,
        ]);

        $builder->add('endDate', TextType::class, [
            'label' => 'To',
            'required' => false,
        ]);

        if (!$options['hidePaymentOption']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => array_flip($orderStatusOptions),
                'required' => false,
                'placeholder' => '-- Select --',
            ]);

            $orderPaymentStatusOptions = PaymentStatusEnum::LABELS;

            $builder->add('paymentStatus', ChoiceType::class, [
                'label' => 'Payment Status',
                'choices' => array_flip($orderPaymentStatusOptions),
                'required' => false,
                'placeholder' => '-- Select --',
            ]);


            $orderPaymentMethodOptions = PaymentMethodEnum::LABELS;

            $builder->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => array_flip($orderPaymentMethodOptions),
                'required' => false,
                'placeholder' => '-- Select --',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
        $resolver->setRequired('hidePaymentOption');
    }

    public function getBlockPrefix()
    {
        return '';
    }

}
