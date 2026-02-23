<?php

namespace App\Service\Ups;

class TimeInTransitPayload implements TimeInTransit
{
    private string $originCountryCode = 'US';
    private string $originPostalCode = '77479';
    private string $originStateProvince = 'TX';
    private string $destinationCountryCode;
    private string $destinationStateProvince;
    private string $destinationPostalCode;
    private ?string $originCityName = 'Sugar Land';
    private ?string $destinationCityName;
    private ?int $weight;
    private ?int $length;
    private ?int $width;
    private ?int $height;

    public function __construct(
        string $originCountryCode,
        string $originPostalCode,
        string $originStateProvince,
        string $destinationCountryCode,
        string $destinationStateProvince,
        string $destinationPostalCode,
        ?string $originCityName = null,
        ?string $destinationCityName = null,
        ?int $weight = null,
        ?int $length = null,
        ?int $width = null,
        ?int $height = null
    ) {
        $this->originCountryCode = $originCountryCode;
        $this->originPostalCode = $originPostalCode;
        $this->originStateProvince = $originStateProvince;
        $this->destinationCountryCode = $destinationCountryCode;
        $this->destinationStateProvince = $destinationStateProvince;
        $this->destinationPostalCode = $destinationPostalCode;
        $this->originCityName = $originCityName;
        $this->destinationCityName = $destinationCityName;
        $this->weight = $weight;
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
    }

    public function getOriginCountryCode(): string
    {
        return $this->originCountryCode;
    }
    public function getOriginPostalCode(): string
    {
        return $this->originPostalCode;
    }
    public function getOriginStateProvince(): string
    {
        return $this->originStateProvince;
    }
    public function getDestinationCountryCode(): string
    {
        return $this->destinationCountryCode;
    }
    public function getDestinationStateProvince(): string
    {
        return $this->destinationStateProvince;
    }

    public function getDestinationPostalCode(): string
    {
        return $this->destinationPostalCode;
    }
    public function getOriginCityName(): ?string
    {
        return $this->originCityName;
    }
    public function getDestinationCityName(): ?string
    {
        return $this->destinationCityName;
    }
    public function getWeight(): ?int
    {
        return $this->weight;
    }
    public function getLength(): ?int
    {
        return $this->length;
    }
    public function getWidth(): ?int
    {
        return $this->width;
    }
    public function getHeight(): ?int
    {
        return $this->height;
    }
}
