<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\State;
use App\Entity\Distributor;
use App\Form\Types\ReCaptchaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use App\DataTransformer\AddressFormCountryStateTransformer;
use Symfony\Component\Form\FormFactoryInterface;
use Doctrine\ORM\EntityRepository;

class DistributorType extends AbstractType
{
    public function __construct(
        private readonly AddressFormCountryStateTransformer         $countryStateTransformer, 
        private readonly EntityManagerInterface                     $entityManager, 
        private FormFactoryInterface                                $factory
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultCountryCode = $address['country'] ?? 'US';
        $defaultCountry = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $defaultCountryCode]);
        $defaultStateCode = $address['state'] ?? null;
        $defaultState = $this->entityManager->getRepository(State::class)->findOneBy(['isoCode' => $defaultStateCode]);
        $builder
            ->add('companyName', TextType::class, [
                'attr' => ['placeholder' => 'Enter company name'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Company name is required']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Company name cannot exceed {{ limit }} characters'
                    ])
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'placeholder' => '(XXX) XXX-XXXX',
                    'maxlength' => 14,
                    'oninput' => 'this.value = this.value.replace(/[^0-9()\-\s]/g, "");' 
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter Phone Number.'),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'The telephone must be at least {{ limit }} characters long.',
                    ]),            
                ]
            ])
            ->add('email', EmailType::class, [
                'attr' => ['placeholder' => 'Enter email address'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email is required']),
                    new Assert\Email(['message' => 'Enter a valid email address'])
                ],
            ])
            ->add('city', TextType::class, [
                'attr' => ['placeholder' => 'Enter city or town'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Enter city or town is required']),
                ],
            ])
            ->add('zipCode', TextType::class, [
                'attr' => ['placeholder' => 'Enter zip or postal code'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Zip code is required']),
                ],
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => 'name',
                'placeholder' => '-- Select Country --',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select a country']),
                ],
                'data' => $defaultCountry,
            ])
            ->add('state', EntityType::class, [
                'class' => State::class,
                'choice_label' => 'name',
                'placeholder' => '-- Select --',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Please select a state']),
                ],
            ])
            ->add('businessType', ChoiceType::class, [
                'choices' => [
                    'Retailer' => 'Retailer',
                    'Wholesaler' => 'Wholesaler',
                    'Online Store' => 'Online Store',
                    'Dealer' => 'Dealer',
                    'Broker' => 'Broker',
                    'Distributor' => 'Distributor',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Select business type',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a business type']),
                ],
            ])
            ->add('businessWebsite', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Website URL (optional)'],
                'constraints' => [
                    new Assert\Url(['message' => 'Enter a valid URL']),
                ],
            ])
            ->add('salesExperience', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Briefly describe your sales experience (optional)',
                    'rows' => 1,
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Sales experience cannot exceed {{ limit }} characters',
                    ])
                ],
            ])
            ->add('expectedMonthlyOrderVolume', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Enter order volume (optional)']
            ])
            ->add('additionalComments', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter Message (optional)',
                    'rows' => 1, 
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Comments cannot exceed {{ limit }} characters',
                    ])
                ],
            ]);

        if (!empty($options['showRecaptcha'])) {
            $builder->add('recaptcha', RecaptchaType::class, [
                'mapped' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($defaultCountry, $defaultState) {
            $this->addStateField($event->getForm(), $defaultCountry, $defaultState);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }



    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $country = $this->entityManager->getRepository(Country::class)->find($data['country'] ?? null);
        $this->addStateField($event->getForm(), $country);

        $form = $event->getForm();

        if (isset($data['recaptcha']) && !$form->has('recaptcha')) {
            $form->add('recaptcha', RecaptchaType::class, [
                'mapped' => false,
            ]);
        }
    }


    public function addStateField(FormInterface $form, Country|string|null $country, State|null $defaultState = null): void
    {
        if (is_string($country)) {
            $country = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $country]);
        }

        $state = $this->factory->createNamedBuilder('state', EntityType::class, $defaultState, [
            'label' => 'State',
            'class' => State::class,
            'choice_label' => 'name',
            'choice_value' => 'id',
            'query_builder' => function (EntityRepository $er) use ($country) {
                return $er->qbEnabledByCountry($country->getId());
            },
            'placeholder' => '-- Select --',
            'constraints' => [new NotBlank()],
            'auto_initialize' => false,
        ]);

        $form->add($state->getForm());
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Distributor::class,
            'default_country' => null,
            'showRecaptcha' => false,
        ]);
    }
}
