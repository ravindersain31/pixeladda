<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Country;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\File;
use App\DataTransformer\AddressFormCountryStateTransformer;
use App\Entity\Address;
use App\Entity\State;
use App\Enum\RolesEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CreateAccountType extends AbstractType
{


    public function __construct(private readonly AddressFormCountryStateTransformer $countryStateTransformer, private readonly EntityManagerInterface $entityManager, private FormFactoryInterface $factory)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $defaultCountryCode = $address['country'] ?? 'US';
        $defaultCountry = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $defaultCountryCode]);
        $defaultStateCode = $address['state'] ?? null;
        $defaultState = $this->entityManager->getRepository(State::class)->findOneBy(['isoCode' => $defaultStateCode]);

        $builder->add('firstName', Type\TextType::class, [
            'label' => 'First Name',
            'attr' => ['placeholder' => 'Enter first name'],
            'constraints' => [
                new Constraints\NotBlank(message: 'This field is required.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('lastName', Type\TextType::class, [
            'label' => 'Last Name',
            'attr' => ['placeholder' => 'Enter last name'],
            'constraints' => [
                new Constraints\NotBlank(message: 'This field is required.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('email', Type\EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Enter email address'],
                'constraints' => [
                    new Constraints\NotBlank(message: 'This field is required.'),
                    new Constraints\Length(['min' => 2, 'max' => 255]),
                    new Constraints\Callback(function ($email, $context) {
                        $userRepo = $this->entityManager->getRepository(AppUser::class);
                        $existing = $userRepo->findOneBy(['username' => $email]);

                        if ($existing && in_array(RolesEnum::USER, $existing->getRoles(), true)) {
                            $context
                                ->buildViolation("We have already registered an account with this email. Please Sign In to your account or use another email.")
                                ->addViolation();
                        }
                    })
                ]
            ]);

        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(message: 'The password confirmation field is required.'),
                new Constraints\Length(['min' => 6, 'max' => 255]),
            ],
            'invalid_message' => 'The password fields must match.',
            'first_options' => [
                'hash_property_path' => 'password',
                'label' => 'Password',
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'password',
                    'placeholder' => "Confirm password"
                ],
            ],
            'mapped' => false,
            'second_options' => [
                'label' => 'Confirm Password',
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'password',
                    'placeholder' => "Enter password"
                ],
                'constraints' => [
                new Constraints\NotBlank(message: 'This field is required.'),
                new Constraints\Length(['min' => 6, 'max' => 255]),
            ],
            ],
        ]);

        $builder->add('referralCode', Type\HiddenType::class, [
            'data' => $options['referralCode'],
        ]);

        if ($options['showUploadField']) {

            $builder->add('city', Type\TextType::class, [
                'label' => 'City',
                'attr' => ['placeholder' => 'Enter city or town'],
                'mapped' => false,
                'constraints' => [
                    new Constraints\NotBlank(message: 'City cannot be empty.'),
                    new Constraints\Length(['min' => 2, 'max' => 255]),
                ]
            ]);

            $builder->add('country', EntityType::class, [
                'label' => 'Country',
                'placeholder' => '--Select--',
                'class' => Country::class,
                'mapped' => false,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [new NotBlank()],
                'data' => $defaultCountry,
            ]);

            $builder->add('zipcode', Type\TextType::class, [
                'label' => 'Zip code',
                'mapped' => false,
                'attr' => ['placeholder' => 'Enter zip or postal code'],
                'constraints' => [
                    new Constraints\NotBlank(message: 'Zip code cannot be empty.'),
                    new Constraints\Length(['min' => 4, 'max' => 20]),
                ]
            ]);

            $builder->add('mobile', Type\TextType::class, [
                'label' => 'Phone Number',
                'attr' => ['placeholder' => 'XXX-XXX-XXXX', 'inputmode' => 'numeric', 'data-phone-input' => true, 'maxlength' => 14],
                'label_attr' => [
                    'class' => 'text-nowrap',
                ],
                'constraints' => [
                    new Constraints\NotBlank(message: 'Phone number cannot be empty.'),
                    new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
                ]
            ]);


            $builder->add('address', Type\TextType::class, [
                'label' => 'Address',
                'mapped' => false,
                'attr' => ['placeholder' => 'Enter street address', 'data-address' => 'autocomplete'],
                'constraints' => [
                    new Constraints\NotBlank(message: 'This field is required.'),
                    new Constraints\Length(['min' => 2, 'max' => 255]),
                ]
            ]);

            $builder->add('companyName', Type\TextType::class, [
                'label' => 'Company Name',
                'mapped' => false,
                'attr' => ['placeholder' => 'Enter Company Name'],
                'constraints' => [
                    new Constraints\NotBlank(message: 'This field is required.'),
                    new Constraints\Length(['min' => 2, 'max' => 255]),
                ]
            ]);

            $builder->add('aboutCompany', Type\TextareaType::class, [
                'label' => 'Tell us about your company',
                'mapped' => false,
                'attr' => [
                    'rows' => 2,
                    'cols' => 50,
                    'placeholder' => 'Enter Tell us about your company',
                ],
                'constraints' => [
                    new Constraints\NotBlank(message: 'This field is required.'),
                ]
            ]);

            $builder->add('website', TextType::class, [
                'label' => 'Website',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Enter Website',
                    'inputmode' => 'url',
                ],
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'This field is required.',
                    ]),
                    new Constraints\Url([
                        'message' => 'Please enter a valid website URL (e.g. https://example.com)',
                        'protocols' => ['http', 'https'],
                    ]),
                    new Constraints\Length([
                        'max' => 255,
                    ]),
                ],
            ]);

            $builder->add('clientType', ChoiceType::class, [
                'label' => 'Client Type',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Sign Shop' => 'Sign Shop',
                    'Promotional Product Distributor' => 'Promotional Product Distributor',
                    'Apparel Decorator' => 'Apparel Decorator',
                    'Commercial Printer' => 'Commercial Printer',
                    'Marketing and Advertising Agency' => 'Marketing and Advertising Agency',
                    'Graphic Designer' => 'Graphic Designer',
                    'Other Reseller Type' => 'Other Reseller Type',
                ],
                'placeholder' => 'Please Select (optional)',
            ]);

            $builder->add('hearAboutUs', ChoiceType::class, [
                'label' => 'How did you hear about us?',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'YouTube' => 'YouTube',
                    'Facebook' => 'Facebook',
                    'ASI / ESP' => 'ASI / ESP',
                    'Referral' => 'Referral',
                    'Sage' => 'Sage',
                    'Trade Show' => 'Trade Show',
                    'Google / Search' => 'Google / Search',
                    'LinkedIn' => 'LinkedIn',
                    'Signs101' => 'Signs101',
                    'Email Blast' => 'Email Blast',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Please Select (optional)',
            ]);


            $builder->add('wholeSellerImageFile', FileType::class, [
                'label' => 'Whole Seller Image File',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => ['image/*', 'application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid image or PDF (max 50MB).',
                    ]),
                ],
            ]);

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($defaultCountry, $defaultState) {
                $this->addStateField($event->getForm(), $defaultCountry, $defaultState);
            });

            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

        }

    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $country = $this->entityManager->getRepository(Country::class)->find($data['country'] ?? null);
        $this->addStateField($event->getForm(), $country);
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
            'mapped' => false,
            'choice_value' => 'id',
            'query_builder' => function(EntityRepository $er) use ($country) {
                return $er->createQueryBuilder('S')
                    ->andWhere('S.country = :country')
                    ->setParameter('country', $country)
                    ->andWhere('S.enabled = :enabled')
                    ->setParameter('enabled', true)
                    ->orderBy('S.name', 'ASC');
            },
            'placeholder' => '-- Select State --',
            'constraints' => [new NotBlank()],
            'auto_initialize' => false,
        ]);

        $form->add($state->getForm());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppUser::class,
            'referralCode' => null,
            'showUploadField' => false,
        ]);
    }
}