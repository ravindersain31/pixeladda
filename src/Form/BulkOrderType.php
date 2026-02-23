<?php

namespace App\Form;

use App\Entity\BulkOrder;
use App\Form\Types\ReCaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints as Assert;

class BulkOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => ['placeholder' => 'Enter first name'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => "/^(?=.*[A-Za-zÀ-ÿ])[A-Za-zÀ-ÿ' -]+$/u",
                        'message' => 'Please enter a valid name (letters only).',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['placeholder' => 'Enter last name'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => "/^(?=.*[A-Za-zÀ-ÿ])[A-Za-zÀ-ÿ' -]+$/u",
                        'message' => 'Please enter a valid name (letters only).',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'Enter email address'],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'placeholder' => '(XXX) XXX-XXXX',
                    'maxlength' => 14,
                    'oninput' => 'this.value = this.value.replace(/[^0-9()\-\s]/g, "");' // restrict input to numbers, parentheses, dash, space
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter Phone Number.'),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'The telephone must be at least {{ limit }} characters long.',
                    ]),            
                ]
            ])
            ->add('company', TextType::class, [
                'label' => 'Company or School Name',
                'attr' => ['placeholder' => 'Enter company or school name'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'required' => false,

                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Quantity cannot be negative.',
                    ]),
                ],
                'attr' => [
                    'min' => 0, // HTML5 validation
                    'placeholder' => 'Enter quantity (optional)'
                ],
            ])
            ->add('budget', ChoiceType::class, [
                'label' => 'Budget',
                'required' => false,
                'choices' => [
                    'No Budget Preference' => 'No Budget Preference',
                    'Under $100' => 'Under $100',
                    '$100 - $500' => '$100 - $500',
                    '$500 - $1000' => '$500 - $1000',
                    'Above $1000' => 'Above $1000',
                ],
                'placeholder' => 'Select budget (optional)',
            ])

            ->add('deliveryDate', DateType::class, [
                'label' => 'Delivery Date',
                'widget' => 'single_text',
                'html5' => false,
                'required' => false,
                'format' => 'dd/mm/yyyy',
                'attr' => [
                    'class' => 'date-picker delivery-date-input-group',
                    'placeholder' =>  'MM/DD/YYYY (optional)',
                    'maxlength' => 10,
                ],
            ])

            ->add('productInInterested', TextareaType::class, [
                'label' => 'Products Interested In',
                'required' => false,
                'attr' => ['placeholder' => 'Select products (optional)'],
            ])

            ->add('comment', TextareaType::class, [
                'label' => 'Comment',
                'required' => false,
                'attr' => ['placeholder' => 'Enter message (optional)'],
            ]);
            if (!empty($options['showRecaptcha'])) {
                $builder->add('recaptcha', RecaptchaType::class, [
                    'mapped' => false,
                ]);
            }
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BulkOrder::class,
            'showRecaptcha' => false,
        ]);
    }
}
