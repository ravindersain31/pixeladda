<?php

namespace App\Form\Admin\Order;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CancelOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('refund', Type\ChoiceType::class, [
            'label' => 'Do you want to process refund with this request?',
            'label_html' => true,
            'choices' => [
                'Yes' => 'YES',
                'No' => 'NO'
            ],
            'data' => 'YES',
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please select Refund Type'])
            ],
            'attr' => ['class' => 'd-flex choices'],
            'expanded' => true,
        ]);

        $builder->add('refundType', Type\ChoiceType::class, [
            'label' => '<b>Refund</b> Type',
            'label_html' => true,
            'choices' => [
                'Full' => 'FULL_REFUND',
                'Partial' => 'PARTIAL_REFUND'
            ],
            'data' => 'FULL_REFUND',
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please select Refund Type'])
            ],
            'attr' => ['class' => 'd-flex choices'],
            'expanded' => true,
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['refund']) && $data['refund'] === 'NO') {
                $form->remove('refundType');
                $form->remove('amount');
            }

            if (isset($data['refundType']) && $data['refundType'] === 'PARTIAL_REFUND') {
                $form->add('amount', Type\TextType::class, [
                    'label' => 'Refund Amount',
                    'constraints' => [
                        new Constraints\Regex([
                            'pattern' => '/^\d+(\.\d{1,2})?$/',
                            'message' => 'Please enter a valid amount.',
                        ]),
                        new Constraints\NotBlank(['message' => 'Please enter a refund amount']),
                        new Constraints\GreaterThan(['value' => 0, 'message' => 'Amount must be greater than zero'])
                    ],
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['refund']) && $data['refund'] === 'YES') {
                $form->add('refundType', Type\ChoiceType::class, [
                    'label' => '<b>Refund</b> Type',
                    'label_html' => true,
                    'choices' => [
                        'Full' => 'FULL_REFUND',
                        'Partial' => 'PARTIAL_REFUND'
                    ],
                    'data' => 'FULL_REFUND',
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please select Refund Type'])
                    ],
                    'attr' => ['class' => 'd-flex choices'],
                    'expanded' => true,
                ]);

                if (isset($data['refundType']) && $data['refundType'] === 'PARTIAL_REFUND') {
                    $form->add('amount', Type\TextType::class, [
                        'label' => 'Refund Amount',
                        'constraints' => [
                            new Constraints\Regex([
                                'pattern' => '/^\d+(\.\d{1,2})?$/',
                                'message' => 'Please enter a valid amount.',
                            ]),
                            new Constraints\NotBlank(['message' => 'Please enter a refund amount']),
                            new Constraints\GreaterThan(['value' => 0, 'message' => 'Amount must be greater than zero'])
                        ],
                    ]);
                }
            } else {
                $form->remove('refundType');
                $form->remove('amount');
            }
        });

        $builder->add('cancellationNotes', Type\TextareaType::class, [
            'label' => 'Cancellation Remarks',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}