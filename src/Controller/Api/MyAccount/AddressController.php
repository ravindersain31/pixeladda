<?php

namespace App\Controller\Api\MyAccount;

use App\Entity\Address;
use App\Service\AddressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/my-account/address')]
class AddressController extends AbstractController
{
    public function __construct(
        private readonly AddressService $addressService,
    ) {}

    #[Route('/{id}', name: 'api_my_account_address_get', methods: ['GET'])]
    public function getAddress(Address $address): JsonResponse
    {
        $user = $this->getUser();

        if ($address->getUser() !== $user) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse('Address retrieved', $this->serializeAddress($address));
    }

    #[Route('', name: 'api_address_create', methods: ['POST'])]
    public function createAddress(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data['shippingAddress'] ?? null) {
            return $this->errorResponse('Invalid JSON', 400);
        }

        $this->addressService->saveAddress(
            $data,
            $this->getUser()
        );

        return $this->successResponse('Address added successfully!', [
            'addresses' => $this->getAllAddresses()
        ]);
    }

    #[Route('/{id}', name: 'api_address_update', methods: ['PUT'])]
    public function updateAddress(Address $address, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data['shippingAddress'] ?? null) {
            return $this->errorResponse('Invalid JSON', 400);
        }

        $this->addressService->updateAddress(
            $address,
            $data,
            $this->getUser()
        );

        return $this->successResponse('Address updated successfully!', [
            'addresses' => $this->getAllAddresses()
        ]);
    }

    #[Route('/{id}', name: 'api_address_delete', methods: ['DELETE'])]
    public function deleteAddress(Address $address): JsonResponse
    {
        $this->addressService->deleteAddress($address, $this->getUser());

        return $this->successResponse('Address deleted successfully!', [
            'addresses' => $this->getAllAddresses()
        ]);
    }

    #[Route('/{id}/default', name: 'api_address_set_default', methods: ['POST'])]
    public function setDefault(Address $address): JsonResponse
    {
        $this->addressService->setDefault($address, $this->getUser());

        return $this->successResponse('Default address updated!', [
            'addresses' => $this->getAllAddresses()
        ]);
    }

    private function getAllAddresses(): array
    {
        return array_map(
            fn(Address $a) => $this->serializeAddress($a),
            $this->addressService->getAllAddresses($this->getUser())
        );
    }

    private function serializeAddress(Address $addr): array
    {
        return [
            'id'           => $addr->getId(),
            'firstName'    => $addr->getFirstName(),
            'lastName'     => $addr->getLastName(),
            'email'        => $addr->getEmail(),
            'phone'        => $addr->getPhone(),
            'addressLine1' => $addr->getAddressLine1(),
            'addressLine2' => $addr->getAddressLine2(),
            'city'         => $addr->getCity(),
            'state'        => $addr->getState() ? [
                'id'      => $addr->getState()->getId(),
                'name'    => $addr->getState()->getName(),
                'isoCode' => $addr->getState()->getIsoCode(),
            ] : null,
            'zipcode'      => $addr->getZipcode(),
            'country'      => $addr->getCountry() ? [
                'id'      => $addr->getCountry()->getId(),
                'name'    => $addr->getCountry()->getName(),
                'isoCode' => $addr->getCountry()->getIsoCode(),
            ] : null,
            'addressTag'   => $addr->getAddressTag(),
            'otherAddressTag'   => $addr->getOtherAddressTag(),
            'isDefault'    => $addr->isDefault(),
        ];
    }

    private function successResponse(string $message, array $data = [], int $code = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data'    => null
        ], $code);
    }
}
