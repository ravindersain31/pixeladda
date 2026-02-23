<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SaveAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Address $address */
        $address = $options['data']; 

        $builder
            ->add('shippingAddress', AddressType::class, [
                'label'  => false,
                'mapped' => false,
                'data'   => $this->prepareAddressData($address),
            ])
            ->add('addressTag', ChoiceType::class, [
                'label'   => 'Address Type',
                'choices' => [
                    'Home'  => 'home',
                    'Work'  => 'work',
                    'Business'  => 'business',
                    'Other' => 'other',
                ],
            ])
            ->add('otherAddressTag', TextType::class, [
                'label'    => 'Secondary Address Type',
                'required' => false,
                'mapped'   => false,
                'attr'     => [
                    'placeholder' => 'Enter secondary address type',
                ],
            ])
            ->add('isDefault', CheckboxType::class, [
                'label'    => 'Make this my default address',
                'required' => false,
            ]);
    }

    private function prepareAddressData(Address $address): array
    {
        return [
            'firstName'    => $address->getFirstName(),
            'lastName'     => $address->getLastName(),
            'addressLine1' => $address->getAddressLine1(),
            'addressLine2' => $address->getAddressLine2(),
            'city'         => $address->getCity(),
            'zipcode'      => $address->getZipcode(),
            'phone'        => $address->getPhone(),
            'email'        => $address->getEmail(),
            'country'      => $address->getCountry()?->getIsoCode(),
            'state'        => $address->getState()?->getIsoCode(),
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
