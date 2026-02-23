<?php

namespace App\Service\EasyPost;

class EasyPostCustomsItem
{

    private ?string $description = 'Plastic Yard Signs';

    private ?string $hsTariffNumber = '3926.90.9990';

    private ?string $originCountry = 'US';

    private ?int $quantity = null;

    private ?float $value = null;

    private ?float $weight = null;

    private ?string $currency = 'USD';

    private ?string $manufacturer = 'Yard Sign Plus';

    private ?string $code = 'CUSTOM';

    public function makePayload(): array
    {
        return [
            'description' => $this->getDescription(),
            'hs_tariff_number' => $this->getHsTariffNumber(),
            'origin_country' => $this->getOriginCountry(),
            'quantity' => $this->getQuantity(),
            'value' => $this->getValue(),
            'weight' => $this->getWeight(),
            'currency' => $this->getCurrency(),
            'manufacturer' => $this->getManufacturer(),
            'code' => $this->getCode(),
            'eccn' => null,
            'printed_commodity_identifier' => null,
        ];
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getHsTariffNumber(): ?string
    {
        return $this->hsTariffNumber;
    }

    public function getOriginCountry(): ?string
    {
        return $this->originCountry;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setValue(?float $value): void
    {
        $this->value = $value;
    }

    public function setWeight(?float $weight): void
    {
        $this->weight = $weight;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }
}