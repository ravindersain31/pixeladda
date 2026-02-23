<?php

namespace App\Form\Admin\Order;

use App\Entity\Country;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class UpdateFromAddressType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['order'];
        $epFromAddress = $order->getMetaDataKey('epFromAddress');
        $addressType = 'ysp';
        if ($epFromAddress && is_array($epFromAddress) && count($epFromAddress) > 0) {
            $addressType = 'custom';
        } else if ($order->hasTag('BLIND_SHIPPING')) {
            $addressType = 'blind';
        }

        $epFromAddressType = $order->getMetaDataKey('epFromAddressType');
        if (in_array($epFromAddressType, ['ysp', 'blind', 'custom'])) {
            $addressType = $epFromAddressType;
        }

        $builder->add('addressType', Type\ChoiceType::class, [
            'label' => false,
            'choices' => [
                'Yard Sign Plus' => 'ysp',
                'Blind Shipping' => 'blind',
                'Custom Address' => 'custom',
            ],
            'choice_attr' => function ($choice, $key, $value) {
                return ['class' => 'address-type-' . $value];
            },
            'expanded' => true,
            'data' => $addressType,
        ]);

        $addressRequired = $addressType === 'custom';
        $builder->add('companyName', Type\TextType::class, [
            'label' => 'Company Name',
            'attr' => ['placeholder' => 'Enter company name'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ],
            'data' => $epFromAddress['company'] ?? null,
        ]);

        $builder->add('name', Type\TextType::class, [
            'label' => 'Name',
            'attr' => ['placeholder' => 'Enter name'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ],
            'data' => $epFromAddress['name'] ?? null,
            'required' => $addressRequired,
        ]);

        $builder->add('street1', Type\TextType::class, [
            'label' => 'Address Line 1',
            'attr' => ['placeholder' => 'Enter street address', 'data-address' => 'autocomplete'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ],
            'data' => $epFromAddress['street1'] ?? null,
            'required' => $addressRequired,
        ]);

        $builder->add('street2', Type\TextType::class, [
            'required' => false,
            'label' => 'Address Line 2',
            'attr' => ['placeholder' => 'Enter apartment, suite, or unit (optional)'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ],
            'data' => $epFromAddress['street2'] ?? null,
        ]);

        $builder->add('city', Type\TextType::class, [
            'label' => 'City',
            'attr' => ['placeholder' => 'Enter city or town'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ],
            'data' => $epFromAddress['city'] ?? null,
            'required' => $addressRequired,
        ]);

        $countryCode = $epFromAddress['country'] ?? "US";
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
            'data' => $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $countryCode]),
            'required' => $addressRequired,
        ]);

        $builder->add('state', Type\TextType::class, [
            'label' => 'State',
            'attr' => ['placeholder' => 'Enter state'],
            'constraints' => [
                new Constraints\Length(['min' => 2, 'max' => 255]),
            ],
            'data' => $epFromAddress['state'] ?? null,
            'required' => $addressRequired,
        ]);

        $builder->add('zipcode', Type\TextType::class, [
            'label' => 'Zip code',
            'attr' => ['placeholder' => 'Enter zip or postal code'],
            'constraints' => [
                new Constraints\Length(['min' => 4, 'max' => 20]),
            ],
            'data' => $epFromAddress['zip'] ?? null,
            'required' => $addressRequired,
        ]);

        $builder->add('phone', Type\TextType::class, [
            'label' => 'Phone Number',
            'attr' => ['placeholder' => 'XXX-XXX-XXXX', 'inputmode' => 'numeric', 'data-phone-input' => true],
            'label_attr' => [
                'class' => 'text-nowrap',
            ],
            'constraints' => [
                new Constraints\Regex(pattern: '^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$^', message: 'Please enter a valid phone number.'),
            ],
            'data' => $epFromAddress['phone'] ?? null,
        ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('order');
    }
}
