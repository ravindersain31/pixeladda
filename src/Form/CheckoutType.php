<?php

namespace App\Form;

use App\Entity\Cart;
use App\Entity\Order;
use App\Enum\PaymentMethodEnum;
use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints;

class CheckoutType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder->add('shippingAddress', AddressType::class, [
            'label' => '<b>Shipping</b> Address',
            'label_html' => true,
        ]);
        $builder->add('billingAddress', AddressType::class, [
            'label' => '<b>Billing</b> Address',
            'label_html' => true,
        ]);
        $builder->add('textUpdates', Type\CheckboxType::class, [
            'label' => 'Mobile Text Updates',
            'required' => false,
        ])->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if ($data->isTextUpdates() && !$data->getTextUpdatesNumber()) {
                $event->getForm()->get('textUpdatesNumber')->addError(new FormError('Mobile Text cannot be empty.'));
            }
        });

        $builder->add('textUpdatesNumber', Type\TextType::class, [
            'label' => false,
            'required' => false,
            'attr' => ['placeholder' => 'XXX-XXX-XXXX', 'inputmode' => 'numeric'],
            'constraints' => [
                new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
            ]
        ]);
        $builder->add('discountForNextOrder', Type\CheckboxType::class, [
            'mapped' => false,
            'required' => false,
            'data' => true,
            'label' => 'Want 10% off on your next purchase?',
            'row_attr' => ['class' => 'mb-0']
        ]);

        $totalAmount = $options['totalAmount'] ?? 0;
        if ($totalAmount > 0) {
            $paymentMethods = PaymentMethodEnum::LABELS;
            unset($paymentMethods[PaymentMethodEnum::STRIPE]);

            if ($totalAmount <= 50) {
                unset($paymentMethods[PaymentMethodEnum::AFFIRM]);
            }
        } else {
            $paymentMethods = [
                PaymentMethodEnum::CHECK => 'Check/PO',
                PaymentMethodEnum::SEE_DESIGN_PAY_LATER => 'See Design Pay Later'
            ];
        }

        /**
         * @var Cart $cart
         */
        $cart = $options['cart'];
        if($cart->isNeedProof() === false && $cart->isDesignApproved() === true) {
            unset($paymentMethods[PaymentMethodEnum::SEE_DESIGN_PAY_LATER]);
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
            'data' => true,
        ]);

        $builder->add('paymentNonce', Type\HiddenType::class, [
            'mapped' => false,
        ]);

        if ($user) {
            $builder->add('addToSavedAddress', Type\CheckboxType::class, [
                'label' => 'Add to Saved Address',
                'label_attr' => ['class' => 'label-text-transform-none'],
                'required' => false,
                'data' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);

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

        if (!empty($options['showRecaptcha'])) {
            $builder->add('recaptcha', ReCaptchaType::class, [
                'mapped' => false,
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Submit Order',
            'attr' => ['class' => 'btn-checkout']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'totalAmount' => 0,
            'showRecaptcha' => false,
            'user' => null,
            'cart' => Cart::class,
        ]);
    }
}
