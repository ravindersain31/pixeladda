<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints as Assert;

class TrackOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('orderId', TextType::class, [
            'label' => 'Enter Order Number',
            'attr' => [
                'placeholder' => 'Enter Order Number',
                'class' => 'order_no',
                'inputmode' => 'numeric',
                'maxlength' => 15,
            ],
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please enter an order ID',
                    'groups' => 'order_id_group'
                ]),
                new Constraints\Regex([
                    'pattern' => '/^[0-9]{6,15}$/',
                    'message' => 'Please enter a valid order ID',
                    'groups' => 'order_id_group'
                ]),

            ]
        ]);

        $builder->add('trackingId', TextType::class, [
            'label' => 'Enter Tracking Number',
            'attr' => [
                'placeholder' => 'Enter Tracking Number',
                'class' => 'order_no',
                'inputmode' => 'numeric',
            ],
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please enter a tracking number',
                    'groups' => 'tracking_group'
                ]),
                new Constraints\Regex([
                    'pattern' => '/^[a-zA-Z0-9\(\)\-]+$/',
                    'message' => 'Please enter a valid tracking number',
                    'groups' => 'tracking_group'
                ]),
            ]
        ]);

        $builder->add('telephone', TextType::class, [
            'label' => 'Enter Your Phone Number',
            'attr' => [
                'placeholder' => '(XXX)-XXX-XXXX',
                'class' => 'telephone',
                'inputmode' => 'numeric'
            ],
            'constraints' => [
                new Constraints\NotBlank([
                    'groups' => 'telephone_group',
                    'message' => 'Please enter valid Phone number'
                ]),
                new Constraints\Regex([
                    'pattern' => '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^',
                    'message' => 'Please enter a valid phone number',
                    'groups' => 'telephone_group'
                ]),
            ]
        ]);

        $builder->add('email', TextType::class, [
            'label' => 'Enter Your Email Address',
            'attr' => [
                'placeholder' => 'Enter Your Email Address',
                'class' => 'email'
            ],
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please enter an email address',
                    'groups' => 'email_group'
                ]),
                new Constraints\Email([
                    'message' => 'Please enter a valid email address',
                    'groups' => 'email_group'
                ]),
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $groups = ['Default'];
                if ($data['orderId']) {
                    $groups[] = 'order_id_group';
                } else if ($data['trackingId']) {
                    $groups[] = 'tracking_group';
                } else {
                    if ($data['email'] || $data['telephone']) {
                        $groups[] = 'email_group';
                        $groups[] = 'telephone_group';
                    } else {
                        $form->addError(new FormError('Please provide either a valid Order Number or Tracking Number or Email Address and Phone Number.'));
                    }
                }
                return $groups;
            },
        ]);
    }
}
