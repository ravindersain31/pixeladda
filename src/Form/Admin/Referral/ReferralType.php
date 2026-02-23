<?php

namespace App\Form\Admin\Referral;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReferralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('startDate', Type\DateType::class, [
            'label' => 'Start Date',
            'widget' => 'single_text',
            'attr' => [
                'class' => 'referral-start-date'
            ],
            'data' => (new \DateTime())->modify('-15 days'),
            'constraints' => [
                new NotBlank(['message' => 'Please select a start date.'])
            ]
        ]);

        $builder->add('endDate', Type\DateType::class, [
            'label' => 'End Date',
            'widget' => 'single_text',
            'attr' => [
                'class' => 'referral-end-date'
            ],
            'data' => new \DateTime(),
            'constraints' => [
                new NotBlank(['message' => 'Please select an end date.'])
            ]
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
            'data_class' => null,
        ]);
    }
}
