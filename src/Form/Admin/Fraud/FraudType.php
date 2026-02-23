<?php

namespace App\Form\Admin\Fraud;

use App\Entity\Country;
use App\Entity\Fraud;
use App\Entity\State;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class FraudType extends AbstractType
{
    private FormFactoryInterface $factory;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->factory = $builder->getFormFactory();

        $address = $options['data'] ?? null;
        $defaultCountryCode = $address?->getCountry() ?? 'US';
        $defaultCountry = $this->entityManager->getRepository(Country::class)->findOneBy(['id' => $defaultCountryCode]);
        $defaultStateCode = $address?->getState() ?? null;
        $defaultState = $this->entityManager->getRepository(State::class)->findOneBy(['id' => $defaultStateCode]);

        $builder->add('firstName', Type\TextType::class, [
                'label' => 'First Name',
                'required' => false,
                'attr' => ['placeholder' => 'Enter first name'],
        ]);
        $builder->add('lastName', Type\TextType::class, [
            'label' => 'Last Name',
            'required' => false,
            'attr' => ['placeholder' => 'Enter last name'],
        ]);
        $builder->add('email', Type\TextType::class, [
            'label' => 'Email Address',
            'required' => false,
            'attr' => ['placeholder' => 'Enter email address'],
            'constraints' => [new Constraints\Email()],
        ]);
        $builder->add('phoneNumber', Type\TextType::class, [
            'label' => 'Phone Number',
            'required' => false,
            'attr' => ['placeholder' => 'XXX-XXX-XXXX', 'inputmode' => 'numeric', 'data-phone-input' => true],
            'label_attr' => ['class' => 'text-nowrap'],
            'constraints' => [new Constraints\Regex(
                pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', 
                message: 'Please enter a valid phone number.'
            )],
        ]);
        $builder->add('city', Type\TextType::class, [
            'label' => 'City',
            'required' => false,
            'attr' => ['placeholder' => 'Enter city'],
        ]);
        $builder->add('zipcode', Type\TextType::class, [
            'label' => 'Zip Code',
            'required' => false,
            'attr' => ['placeholder' => 'Enter zip code'],
        ]);

        $builder->add('addressLine1', Type\TextType::class, [
            'label' => 'Address Line 1',
            'required' => false,
            'attr' => ['placeholder' => 'Enter street address', 'data-address' => 'autocomplete'],
        ]);

        $builder->add('country', EntityType::class, [
            'label' => 'Country',
            'placeholder' => '--Select--',
            'class' => Country::class,
            'choice_value' => 'isoCode',
            'required' => false,
            'query_builder' => function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('C');
                return $qb->andWhere($qb->expr()->eq('C.enabled', ':enabled'))
                    ->setParameter('enabled', true)
                    ->orderBy('C.name', 'ASC');
            },
            'choice_label' => 'name',
            'data' => $defaultCountry,
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Submit',
            'attr' => ['placeholder' => 'Submit'],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($defaultCountry, $defaultState) {
            $this->addStateField($event->getForm(), $defaultCountry, $defaultState);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if (empty($data->getFirstName()) && empty($data->getLastName()) && empty($data->getEmail()) && empty($data->getPhoneNumber()) && empty($data->getAddressLine1())) {
                $form->addError(new FormError('At least one field must be filled.'));
            }
        });
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $country = $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $data['country'] ?? 'US']);
        $this->addStateField($event->getForm(), $country);
    }

    public function addStateField(FormInterface $form, Country|null $country, State|null $defaultState = null): void
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
            'required' => false,
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
        ]);

        $form->add($state->getForm());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Fraud::class,
        ]);
    }
}
