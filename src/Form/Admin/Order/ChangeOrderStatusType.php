<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Service\OrderLogger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangeOrderStatusType extends AbstractType
{

    private Order $order;

    public function __construct(private readonly OrderLogger $orderLogger) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $this->order = $options['order'];

        //        $orderStatusOptions = OrderStatusEnum::LABELS;
        //        unset($orderStatusOptions[OrderStatusEnum::CREATED]);
        //        unset($orderStatusOptions[OrderStatusEnum::CHANGES_REQUESTED]);
        //        unset($orderStatusOptions[OrderStatusEnum::DESIGNER_ASSIGNED]);
        //        unset($orderStatusOptions[OrderStatusEnum::CANCELLED]);
        //        unset($orderStatusOptions[OrderStatusEnum::REFUNDED]);
        //        unset($orderStatusOptions[OrderStatusEnum::PARTIALLY_REFUNDED]);
        //
        $builder->add('status', ChoiceType::class, [
            'label' => 'Order Status',
            'choices' => array_flip(OrderStatusEnum::CHANGE_STATUS_LABELS),
            'data' => null,
            'placeholder' => '-- Select --',
        ]);
        //
        //        if (in_array($this->order->getStatus(), [OrderStatusEnum::CREATED, OrderStatusEnum::RECEIVED, OrderStatusEnum::DESIGNER_ASSIGNED, OrderStatusEnum::CHANGES_REQUESTED, OrderStatusEnum::PROOF_UPLOADED])) {
        //            $builder->add('warnOrderStatus', CheckboxType::class, [
        //                'label' => '<div class="px-3 py-0 mb-0 alert alert-warning"><b>The proofs are not approved yet.</b> Tick me if you still want to proceed.</div>',
        //                'label_html' => true,
        //                'constraints' => [
        //                    new NotBlank([
        //                        'message' => 'Please tick me to proceed.',
        //                    ]),
        //                ],
        //            ]);
        //        }
        //
        //        if ($this->order->getPaymentStatus() != PaymentStatusEnum::COMPLETED) {
        //            $builder->add('warnPaymentStatus', CheckboxType::class, [
        //                'label' => '<div class="px-3 py-0 mb-0 alert alert-warning"><b>The payment is not received yet.</b> Tick me if you still want to proceed.</div>',
        //                'label_html' => true,
        //                'constraints' => [
        //                    new NotBlank([
        //                        'message' => 'Please tick me to proceed.',
        //                    ]),
        //                ],
        //            ]);
        //        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Submit',
            'row_attr' => [
                'class' => 'mb-0'
            ],
            'attr' => [
                'class' => 'btn btn-primary btn-sm'
            ]
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onSubmit']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired(['order']);
    }

    public function onSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $changes = $this->identifyChanges($data, $this->order);
            if (!empty($changes)) {
                $this->order->setStatus($data['status']);
                $this->order->setUpdatedAt(new \DateTimeImmutable());
                $this->orderLogger->setOrder($this->order);
                $this->orderLogger->log($changes);
            }

            $this->orderLogger->entityManager->persist($this->order);
            $this->orderLogger->entityManager->flush();

            $this->orderLogger->addFlash('success', 'Order status has been changed successfully.');
        }
    }

    public function identifyChanges(array $data, Order $order): string
    {
        $content = '';
        if ($data['status'] != $order->getStatus()) {
            $content .= 'Order status changed to <b>' . OrderStatusEnum::LABELS[$data['status']] . '</b> from <b>' . OrderStatusEnum::LABELS[$order->getStatus()] . '</b>. ';
        }
        return $content;
    }
}
