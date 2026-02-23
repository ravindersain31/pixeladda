<?php

namespace App\Service\EasyPost;

use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPostAddress extends Base
{
    use EasyPostAwareTrait;

    private ?string $name = null;

    private ?string $company = null;

    private ?string $street1 = null;

    private ?string $street2 = null;

    private ?string $city = null;

    private ?string $state = null;

    private ?string $zip = null;

    private ?string $email = null;

    private ?string $phone = null;

    private ?string $country = null;

    private ?bool $residential = null;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct($parameterBag);
    }

    public function create(bool $verify = true): array
    {
        try {
            $address = $this->makeAddressPayload();

            $client = $this->getClient();
            if ($verify) {
                $response = $client->address->createAndVerify($address);
            } else {
                $response = $client->address->create($address);
            }
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            return [
                'success' => true,
                'message' => 'Address created successfully.',
                'address' => $this->formatAddressFromResponse($response),
            ];
        } catch (InvalidRequestException $exception) {
            $parsedMessage = $this->parseErrorsAsMessage($exception->errors);
            return [
                'success' => false,
                'message' => $exception->getMessage() . ' ' . $parsedMessage,
                'error' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }


    public function get(string $addressId)
    {
        try {
            $client = $this->getClient();
            $response = $client->address->retrieve($addressId);
            $messageResponse = $this->parseMessages($response);
            if (!$messageResponse['success']) {
                return $messageResponse;
            }

            return [
                'success' => true,
                'message' => 'Address retrieved successfully.',
                'address' => $this->formatAddressFromResponse($response),
            ];
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => 'Requested address not found. Please provide a valid address id.',
            ];
        }
    }

    private function formatAddressFromResponse(mixed $response): array
    {
        $data = $response->__toArray();
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'company' => $data['company'],
            'street1' => $data['street1'],
            'street2' => $data['street2'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'residential' => $data['residential'] ?? null,
        ];
    }

    private function makeAddressPayload(): array
    {
//        if (empty($this->name) || empty($this->street1) || empty($this->city) || empty($this->state) || empty($this->zip) || empty($this->phone)) {
//            throw new \Exception('Address is not complete. Please set all required fields.');
//        }

        return [
            'name' => $this->name,
            'company' => $this->company,
            'street1' => $this->street1,
            'street2' => $this->street2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'residential' => $this->residential,
        ];
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function setStreet1(?string $street1): void
    {
        $this->street1 = $street1;
    }

    public function setStreet2(?string $street2): void
    {
        $this->street2 = $street2;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function setZip(?string $zip): void
    {
        $this->zip = $zip;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function setResidential(?bool $residential): void
    {
        $this->residential = $residential;
    }
    public function isResidential(): ?bool
    {
        return $this->residential;
    }

}