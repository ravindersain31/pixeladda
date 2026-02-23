<?php

namespace App\Form;

use App\Entity\Order;
use App\Enum\PaymentMethodEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentLinkType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $paymentMethods = PaymentMethodEnum::LABELS;
        unset($paymentMethods[PaymentMethodEnum::CHECK]);
        unset($paymentMethods[PaymentMethodEnum::SEE_DESIGN_PAY_LATER]);
        unset($paymentMethods[PaymentMethodEnum::STRIPE]);

        $totalAmount = $options['totalAmount'] ?? 0;
        if ($totalAmount <= 50) {
            unset($paymentMethods[PaymentMethodEnum::AFFIRM]);
        }

        $builder->add('paymentMethod', Type\ChoiceType::class, [
            'label' => '<b>Payment</b> Method',
            'label_html' => true,
            'data' => PaymentMethodEnum::CREDIT_CARD,
            'choices' => array_flip($paymentMethods),
            'expanded' => true,
            'choice_attr' => function ($choice, $key, $value) {
                $activeClass = $value === PaymentMethodEnum::CREDIT_CARD ? ' active' : '';
                return [
                    'class' => 'form-check-input ' . $activeClass,
                    'data-bs-toggle' => 'tab',
                    'data-bs-target' => '#payment-method-' . strtolower($value),
                    'data-help-message' => PaymentMethodEnum::HELP_MESSAGES[$value],
                    'data-payment-method' => $value,
                    'data-payment-method-label' => $key,
                ];
            },
        ]);

        $builder->add('agreeTerms', Type\CheckboxType::class, [
            'label' => 'I have read and accept all of the <a href="' . $this->urlGenerator->generate('terms_and_conditions') . '" target="_blank">Terms of Conditions</a>.',
            'label_html' => true,
        ]);

        $builder->add('paymentNonce', Type\HiddenType::class, [
            'mapped' => false,
        ]);

        if ($user) {
            $builder->add('savedCardToken', Type\HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);

            $builder->add('saveCard', Type\CheckboxType::class, [
                'label' => 'Save this payment method for future purchases',
                'mapped' => false,
                'required' => false,
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Pay Now',
            'attr' => ['class' => 'btn-checkout']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'totalAmount' => 0,
            'user' => null,
        ]);
    }
}
