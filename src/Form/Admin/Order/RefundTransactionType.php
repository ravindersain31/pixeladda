<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class RefundTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('refundType', Type\ChoiceType::class, [
            'label' => '<b>Refund</b> Type',
            'label_html' => true,
            'choices' => [
                'Full' => 'FULL_REFUND',
                'Partial' => 'PARTIAL_REFUND'
            ],
            'constraints' => [
                new Constraints\NotBlank(null, 'Please select Refund Type')
            ],
            'attr' => ['class' => 'd-flex choices'],
            'expanded' => true,
        ]);

        $amountFieldConfig = [
            'label' => '<b>Refund</b> Amount',
            'label_html' => true,
            'constraints' => [
                new Constraints\Regex([
                    'pattern' => '/^\d+(\.\d{1,2})?$/',
                    'message' => 'Please enter a valid amount.',
                ]),
                new Constraints\GreaterThan(0),
            ],
        ];
        $builder->add('amount', Type\TextType::class, $amountFieldConfig);


        $builder->add('internalNote', Type\TextareaType::class, [
            'label' => '<b>Internal</b> Note',
            'label_html' => true,
            'constraints' => [
                new Constraints\NotBlank(),
            ]
        ]);

        $builder->add('customerNote', Type\TextareaType::class, [
            'label' => '<b>Customer</b> Note',
            'label_html' => true,
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($amountFieldConfig) {
            $form = $event->getForm();
            $data = $form->getData();
            if ($data['refundType'] === 'PARTIAL_REFUND' && $data['amount'] <= 0) {
                $form->get('amount')->addError(new FormError('Please enter the amount to issue a partial refund.'));
            }
        });

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
