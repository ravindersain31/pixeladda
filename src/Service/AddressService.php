<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Enum\AddressTypeEnum;
use App\Repository\AddressRepository;
use App\Repository\CountryRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AddressService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AddressRepository      $addressRepo,
        private readonly CountryRepository      $countryRepo,
        private readonly StateRepository        $stateRepo,
    ) {}

    public function getAllAddresses(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return $this->addressRepo->findAddressesByUser($user);
    }

    public function createAddress(array $data, User $user, string $addressType = AddressTypeEnum::SHIPPING->value): Address
    {
        $address = new Address();
        $this->mapToEntity($address, $data[$addressType] ?? []);
        
        $addressTag = $data['addressTag'] ?? null;
        $otherAddressTag   = $data['otherAddressTag'] ?? null;

        $address->setAddressTag($addressTag);
        $address->setOtherAddressTag($addressTag === 'other' ? $otherAddressTag : null);
        $address->setAddressType($addressType);
        $address->setUser($user);

        $isDefault = (bool)($data['isDefault'] ?? false);
        $address->setIsDefault($isDefault);
        $this->em->persist($address);
        $this->em->flush();

        $this->syncDefaultAddress($user, $isDefault ? $address : null);

        return $address;
    }

    public function updateAddress(Address $address, array $data, User $user, string $addressType = AddressTypeEnum::SHIPPING->value): Address
    {
        if ($address->getUser() !== $user) {
            throw new AccessDeniedException('You cannot edit this address.');
        }

        $this->mapToEntity($address, $data[$addressType] ?? []);
        $addressTag = $data['addressTag'] ?? null;
        $otherAddressTag   = $data['otherAddressTag'] ?? null;

        $address->setAddressTag($addressTag);
        $address->setOtherAddressTag($addressTag === 'other' ? $otherAddressTag : null);
        $address->setAddressType($addressType);
        $address->setUser($user);

        $isDefault = (bool)($data['isDefault'] ?? false);
        $address->setIsDefault($isDefault);

        $this->em->persist($address);
        $this->em->flush();

        $this->syncDefaultAddress($user, $isDefault ? $address : null);

        return $address;
    }

    public function deleteAddress(Address $address, User $user): void
    {
        if ($address->getUser() !== $user) {
            throw new AccessDeniedException('You cannot delete this address.');
        }

        $this->em->remove($address);
        $this->em->flush();

        $this->syncDefaultAddress($user);
    }

    public function setDefault(Address $address, User $user): void
    {
        if ($address->getUser() !== $user) {
            throw new AccessDeniedException('You cannot set this address as default.');
        }

        $address->setIsDefault(true);
        $this->em->persist($address);
        $this->em->flush();

        $this->syncDefaultAddress($user, $address);
    }

    public function saveAddress(array $data, User $user, string $addressType = AddressTypeEnum::SHIPPING->value): Address
    {
        return $this->createAddress($data, $user, $addressType);
    }

    public function syncDefaultAddress(User $user, ?Address $selectedDefault = null): void
    {
        $addresses = $this->addressRepo->findBy(['user' => $user]);

        if (!$addresses) {
            return;
        }

        if ($selectedDefault !== null) {
            foreach ($addresses as $addr) {
                $addr->setIsDefault($addr === $selectedDefault);
                $this->em->persist($addr);
            }
            $this->em->flush();
            return;
        }

        foreach ($addresses as $addr) {
            if ($addr->isDefault()) {
                return;
            }
        }

        $firstAddress = reset($addresses); 
        if ($firstAddress instanceof Address) {
            $firstAddress->setIsDefault(true);
            $this->em->persist($firstAddress);
            $this->em->flush();
        }
    }

    private function mapToEntity(Address $address, array $data): void
    {
        $country = null;
        if (!empty($data['country'])) {
            $country = is_numeric($data['country'])
                ? $this->countryRepo->find($data['country'])
                : $this->countryRepo->findOneBy(['isoCode' => $data['country']]);
        }

        $state = null;
        if (!empty($data['state']) && $country) {
            $state = is_numeric($data['state'])
                ? $this->stateRepo->find($data['state'])
                : $this->stateRepo->findOneBy([
                    'isoCode' => $data['state'],
                    'country' => $country
                ]);
        }

        $address
            ->setFirstName($data['firstName'] ?? null)
            ->setLastName($data['lastName'] ?? null)
            ->setAddressLine1($data['addressLine1'] ?? null)
            ->setAddressLine2($data['addressLine2'] ?? null)
            ->setCity($data['city'] ?? null)
            ->setState($state)
            ->setCountry($country)
            ->setZipcode($data['zipcode'] ?? null)
            ->setPhone($data['phone'] ?? null)
            ->setEmail($data['email'] ?? null);
    }
}
