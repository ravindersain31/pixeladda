<?php
namespace App\Service\Ups;

/**
 * Interface for defining the structure of a TimeInTransit request payload.
 */
interface TimeInTransit
{
    public function getOriginCountryCode(): string;
    public function getOriginPostalCode(): string;
    public function getOriginStateProvince(): string;
    public function getDestinationCountryCode(): string;
    public function getDestinationStateProvince(): string;
    public function getDestinationPostalCode(): string;
    public function getOriginCityName(): ?string;
    public function getDestinationCityName(): ?string;
    public function getWeight(): ?int;
    public function getLength(): ?int;
    public function getWidth(): ?int;
    public function getHeight(): ?int;
}
