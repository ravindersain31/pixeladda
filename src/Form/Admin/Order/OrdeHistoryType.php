<?php

namespace App\Form\Admin\Order;

use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrdeHistoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $start = (new \DateTime())->modify('-31 days');
        $end   = (clone $start)->modify('+31 days');
        $builder->add('startDate', Type\DateType::class, [
            'label' => 'Start Date',
            'widget' => 'single_text',
            'attr' => [
                'class' => ''
            ],
            'data' => $start,
            'constraints' => [
                new NotBlank(message: 'Please select a date.')
            ]
        ]);

        $builder->add('endDate', Type\DateType::class, [
            'label' => 'End Date',
            'widget' => 'single_text',
            'attr' => [
                'class' => ''
            ],
            'data' => $end,
            'constraints' => [
                new NotBlank(message: 'Please select a date.')
            ]
        ]);

        $builder->add('orderStatus', Type\ChoiceType::class, [
            'label' => 'Order Status',
            'choices' => array_flip(OrderStatusEnum::LABELS),
            'multiple' => true,
            'expanded' => false,
            'placeholder' => '-- Select Order Status --',
            'autocomplete' => true,
            'required' => false,
        ]);

        $builder->add('paymentStatus', Type\ChoiceType::class, [
            'label' => 'Payment Status',
            'choices' => array_flip(PaymentStatusEnum::LABELS),
            'multiple' => true,
            'expanded' => false,
            'placeholder' => '-- Select Payment Status --',
            'autocomplete' => true,
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if ($data['startDate'] > $data['endDate']) {
                $form->get('startDate')->addError(new FormError('The start date should not be later than the end date.'));
            }

            if ($data['endDate'] < $data['startDate']) {
                $form->get('endDate')->addError(new FormError('The end date should not be earlier than the start date.'));
            }
        });
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => null,
        ]);
    }
}
