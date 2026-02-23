<?php

namespace App\Form\Admin\Customer;

use App\Entity\WholeSeller;
use App\Entity\Country;
use App\Entity\State;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\DataTransformer\AddressFormCountryStateTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use App\Enum\RolesEnum;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;



class WholeSellerType extends AbstractType
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

        $builder
            ->add('mobile', Type\TextType::class, [
                'label' => 'Mobile',
                'attr' => ['placeholder' => 'XXX-XXX-XXXX', 'inputmode' => 'numeric', 'data-phone-input' => true],
                'label_attr' => [
                    'class' => 'text-nowrap',
                ],
                'constraints' => [
                    new NotBlank(message: 'Phone number cannot be empty.'),
                    new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
                ]
            ])
            ->add('city', Type\TextType::class, [
                'label' => 'City',
                'attr' => ['placeholder' => 'Enter city or town'],
                'constraints' => [
                    new NotBlank(['message' => 'City cannot be empty.']),
                    new Length(['min' => 2, 'max' => 255]),
                ],
            ])

            ->add('zipcode', Type\TextType::class, [
                'label' => 'Zip code',
                'attr' => ['placeholder' => 'Enter zip or postal code'],
                'constraints' => [
                    new NotBlank(['message' => 'Zip code cannot be empty.']),
                    new Length(['min' => 4, 'max' => 20]),
                ],
            ])

            ->add('country', EntityType::class, [
                'label' => 'Country',
                'placeholder' => '--Select--',
                'class' => Country::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [new NotBlank()],
                'data' => $defaultCountry,
            ])

            ->add('address', Type\TextType::class, [
                'label' => 'Address',
                'attr' => ['placeholder' => 'Enter address'],
                'constraints' => [
                    new NotBlank(['message' => 'This field is required.']),
                    new Length(['min' => 2, 'max' => 255]),
                ],
            ])

            ->add('companyName', Type\TextType::class, [
                'label' => 'Company Name',
                'attr' => ['placeholder' => 'Enter Company Name'],
                'constraints' => [
                    new NotBlank(['message' => 'This field is required.']),
                    new Length(['min' => 2, 'max' => 255]),
                ],
            ])

            ->add('aboutCompany', Type\TextareaType::class, [
                'label' => 'Tell us about your company',
                'attr' => ['rows' => 2, 'placeholder' => 'Enter company description'],
                'constraints' => [
                    new NotBlank(['message' => 'This field is required.']),
                ],
            ])

            ->add('website', Type\TextType::class, [
                'label' => 'Website',
                'attr' => ['placeholder' => 'Enter website URL'],
                'constraints' => [
                    new NotBlank(['message' => 'This field is required.']),
                    new Length(['min' => 2, 'max' => 255]),
                ],
            ])

            ->add('clientType', Type\ChoiceType::class, [
                'label' => 'Client Type',
                'required' => false,
                'placeholder' => 'Please Select',
                'choices' => [
                    'Sign Shop' => 'Sign Shop',
                    'Promotional Product Distributor' => 'Promotional Product Distributor',
                    'Apparel Decorator' => 'Apparel Decorator',
                    'Commercial Printer' => 'Commercial Printer',
                    'Marketing and Advertising Agency' => 'Marketing and Advertising Agency',
                    'Graphic Designer' => 'Graphic Designer',
                    'Other Reseller Type' => 'Other Reseller Type',
                ],
            ])

            ->add('hearAboutUs', Type\ChoiceType::class, [
                'label' => 'How did you hear about us?',
                'required' => false,
                'placeholder' => 'Please Select',
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
            ])

            ->add('wholeSellerImageFile', Type\FileType::class, [
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
            'data_class' => WholeSeller::class,
        ]);
    }
}
