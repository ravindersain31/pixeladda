<?php

namespace App\Form;

use App\DataTransformer\AddressFormCountryStateTransformer;
use App\Entity\Country;
use App\Entity\State;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class AddressType extends AbstractType
{
    private FormFactoryInterface $factory;

    public function __construct(private readonly AddressFormCountryStateTransformer $countryStateTransformer, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->factory = $builder->getFormFactory();

        $address = $options['data'] ?? null;
        $defaultCountryCode = $address['country'] ?? 'US';
        $defaultCountry = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $defaultCountryCode]);
        $defaultStateCode = $address['state'] ?? null;
        $defaultState = $this->entityManager->getRepository(State::class)->findOneBy(['isoCode' => $defaultStateCode]);

        $builder->add('firstName', Type\TextType::class, [
            'label' => 'First Name',
            'attr' => ['placeholder' => 'Enter first name'],
            'constraints' => [
                new Constraints\NotBlank(message: 'First Name cannot be empty.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('lastName', Type\TextType::class, [
            'label' => 'Last Name',
            'attr' => ['placeholder' => 'Enter last name'],
            'constraints' => [
                new Constraints\NotBlank(message: 'Last Name cannot be empty.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('addressLine1', Type\TextType::class, [
            'label' => 'Address Line 1',
            'attr' => ['placeholder' => 'Enter street address', 'data-address' => 'autocomplete'],
            'constraints' => [
                new Constraints\NotBlank(message: 'Address cannot be empty.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('addressLine2', Type\TextType::class, [
            'required' => false,
            'label' => 'Address Line 2',
            'attr' => ['placeholder' => 'Enter apartment, suite, or unit (optional)'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('city', Type\TextType::class, [
            'label' => 'City',
            'attr' => ['placeholder' => 'Enter city or town'],
            'constraints' => [
                new Constraints\NotBlank(message: 'City cannot be empty.'),
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ]
        ]);

        $builder->add('country', EntityType::class, [
            'label' => 'Country',
            'placeholder' => '--Select--',
            'class' => Country::class,
            'choice_value' => 'isoCode',
            'query_builder' => function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('C');
                return $qb->andWhere($qb->expr()->eq('C.enabled', ':enabled'))
                    ->setParameter('enabled', true)
                    ->orderBy('C.name', 'ASC');
            },
            'choice_label' => 'name',
            'constraints' => [
                new Constraints\NotBlank(message: 'Country cannot be empty.'),
            ],
            'data' => $defaultCountry,
        ]);

        $builder->add('zipcode', Type\TextType::class, [
            'label' => 'Zip code',
            'attr' => ['placeholder' => 'Enter zip or postal code'],
            'constraints' => [
                new Constraints\NotBlank(message: 'Zip code cannot be empty.'),
                new Constraints\Length(['min' => 4, 'max' => 20]),
            ]
        ]);

        $builder->add('phone', Type\TextType::class, [
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

        $builder->add('email', Type\TextType::class, [
            'label' => 'Email Address',
            'attr' => ['placeholder' => 'Enter email address', 'data-save-cart-email' => true],
            'constraints' => [
                new Constraints\NotBlank(message: 'Email address cannot be empty.'),
                new Constraints\Email(),
            ]
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($defaultCountry, $defaultState) {
            $this->addStateField($event->getForm(), $defaultCountry, $defaultState);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

        $builder->addModelTransformer($this->countryStateTransformer);

    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $country = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $data['country'] ?? 'US']);
        $this->addStateField($event->getForm(), $country);
    }

    public function addStateField(FormInterface $form, Country|string|null $country, State|null $defaultState = null): void
    {
        if (is_string($country)) {
            $country = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $country]);
        }

        $state = $this->factory->createNamedBuilder('state', EntityType::class, null, [
            'label' => 'State',
            'class' => State::class,
            'choice_label' => 'name',
            'choice_value' => 'isoCode',
            'data' => $defaultState,
            'query_builder' => function (EntityRepository $er) use ($country) {
                $qb = $er->createQueryBuilder('S');
                return $qb->andWhere($qb->expr()->eq('S.country', ':country'))
                    ->setParameter('country', $country)
                    ->andWhere($qb->expr()->eq('S.enabled', ':enabled'))
                    ->setParameter('enabled', true)
                    ->orderBy('S.name', 'ASC');
            },
            'placeholder' => '-- Select State --',
            'auto_initialize' => false,
            'invalid_message' => false,
            'constraints' => [
                new Constraints\NotBlank(message: 'State cannot be empty.'),
            ],
        ]);

        $form->add($state->getForm());
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
