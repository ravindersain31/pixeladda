<?php

namespace App\Form;

use App\Enum\PaymentMethodEnum;
use App\Form\Types\UserFileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Vich\UploaderBundle\Form\Type\VichFileType;

class RequestChangesType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $showPaymentOption = $options['data']['showPaymentOption'];

        if ($showPaymentOption) {
            $paymentMethods = PaymentMethodEnum::LABELS;
            unset($paymentMethods[PaymentMethodEnum::SEE_DESIGN_PAY_LATER]);
            unset($paymentMethods[PaymentMethodEnum::STRIPE]);
            unset($paymentMethods[PaymentMethodEnum::CHECK]);
            unset($paymentMethods[PaymentMethodEnum::GOOGLE_PAY]);
            unset($paymentMethods[PaymentMethodEnum::PAYPAL]);
            unset($paymentMethods[PaymentMethodEnum::AFFIRM]);

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

        $builder->add('changes', TextareaType::class, [
            'label' => 'Request Changes',
            'attr' => ['placeholder' => 'Add comments here to revise your proof.'],
        ]);
        $builder->add('files', LiveCollectionType::class, [
            'label' => 'Attach Files/Logos',
            'entry_type' => FileType::class,
            'button_add_options' => [
                'label' => 'Add File',
                'attr' => [
                    'class' => 'btn btn-dark btn-sm',
                ],
            ],
            'allow_delete' => true,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-danger btn-sm',
                ],
            ],
            'entry_options' => [
                'label' => false,
                'row_attr' => ['class' => 'mb-3 me-3 w-100']
            ],
            'row_attr' => ['class' => 'd-none'],
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit Changes',
            'row_attr' => ['class' => 'text-center'],
            'attr' => [
                'class' => 'btn-primary btn-lg mt-3',
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
