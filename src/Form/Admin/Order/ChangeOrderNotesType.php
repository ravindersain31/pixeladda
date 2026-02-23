<?php

namespace App\Form\Admin\Order;

use App\Entity\Order;
use App\Service\OrderLogger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeOrderNotesType extends AbstractType
{

    private Order $order;

    public function __construct(private readonly OrderLogger $orderLogger)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $this->order = $options['order'];

        $builder->add('notes', TextareaType::class, [
            'label' => 'Notes',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter notes for this order, it will be saved in logs'
            ]
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Save Notes',
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
                $this->order->setUpdatedAt(new \DateTimeImmutable());
                $this->orderLogger->setOrder($this->order);
                $this->orderLogger->log($changes);
            }

            $this->order->setProofDesigner(null);
            $this->orderLogger->entityManager->persist($this->order);
            $this->orderLogger->entityManager->flush();

            $this->orderLogger->addFlash('success', 'Order notes has been added in the Logs tab successfully.');
        }
    }

    public function identifyChanges(array $data, Order $order): string
    {
        $content = '';
        if (!empty($data['notes'])) {
            $content .= '<b>Notes:</b> ' . $data['notes'];
        }
        return $content;
    }
}
