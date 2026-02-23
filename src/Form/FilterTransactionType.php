<?php

namespace App\Form;

use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('transactionId', TextType::class, [
            'label' => 'Search',
            'attr' => ['placeholder' => 'Search by Transaction Id'],
            'required' => false,
        ]);

        $orderPaymentMethodOptions = PaymentMethodEnum::LABELS;
        
        $builder->add('paymentMethod', ChoiceType::class, [
            'label' => 'Payment Method',
            'choices' => array_flip($orderPaymentMethodOptions),
            'required' => false,
            'placeholder' => 'Any',
        ]);
        $orderPaymentStatusOptions = PaymentStatusEnum::LABELS;

        $builder->add('status', ChoiceType::class, [
            'label' => 'Transaction Status',
            'choices' => array_flip($orderPaymentStatusOptions),
            'required' => false,
            'placeholder' => 'Any',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
