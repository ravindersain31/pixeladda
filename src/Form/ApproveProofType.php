<?php

namespace App\Form;

use App\Entity\Order;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApproveProofType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $showPaymentOption = $options['data']['showPaymentOption'];
        $totalAmount = $options['data']['totalAmount'];
        $isLoggedIn = $options['data']['isLoggedIn'];

        if ($showPaymentOption) {
            $paymentMethods = PaymentMethodEnum::LABELS;
            unset($paymentMethods[PaymentMethodEnum::SEE_DESIGN_PAY_LATER]);
            unset($paymentMethods[PaymentMethodEnum::STRIPE]);

            if ($totalAmount <= 50) {
                unset($paymentMethods[PaymentMethodEnum::AFFIRM]);
            }

            $builder->add('paymentMethod', ChoiceType::class, [
                'label' => '<b>Payment</b> Method',
                'label_html' => true,
                'data' => PaymentMethodEnum::CREDIT_CARD,
                'choices' => array_flip($paymentMethods),
                'expanded' => true,
                'choice_attr' => function ($choice, $key, $value) {
                    $activeClass = $value === PaymentMethodEnum::CREDIT_CARD ? ' active' : '';
                    return [
                        'class' => 'form-check-input ' . $activeClass .' '. $value,
                        'data-bs-toggle' => 'tab',
                        'data-bs-target' => '#payment-method-' . strtolower($value),
                        'data-help-message' => PaymentMethodEnum::HELP_MESSAGES[$value],
                        'data-payment-method' => $value,
                        'data-payment-method-label' => $key,
                    ];
                },
            ]);

            $builder->add('paymentNonce', HiddenType::class);

            $builder->add('agreeTerms', CheckboxType::class, [
                'label' => 'I have read and accept all of the <a href="' . $this->urlGenerator->generate('terms_and_conditions') . '" target="_blank">Terms of Conditions</a>.',
                'label_html' => true,
                'data' => true,
            ]);
        }

        $builder->add('agreeTerms', CheckboxType::class, [
            'label' => 'I have read and accept all of the <a href="' . $this->urlGenerator->generate('terms_and_conditions') . '" target="_blank">Terms of Conditions</a>.',
            'label_html' => true,
            'data' => true,
        ]);

        if ($isLoggedIn){
            $builder->add('savedCardToken', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);

            $builder->add('saveCard', CheckboxType::class, [
                'label' => 'Save this payment method for future purchases',
                'mapped' => false,
                'required' => false,
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Approve Proof & Begin Production',
            'row_attr' => ['class' => 'text-center'],
            'attr' => [
                'class' => 'bg-primary btn-lg text-white',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
