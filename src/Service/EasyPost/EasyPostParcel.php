<?php

namespace App\Service\EasyPost;

use EasyPost\Exception\Api\InvalidRequestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EasyPostParcel extends Base
{
    private ?string $signSize = null;

    private ?string $quantity = null;

    private ?string $length = null;

    private ?string $width = null;

    private ?string $height = null;

    private ?string $weight = null;

    private ?string $unit = null;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct($parameterBag);
    }

    public function createParcels(array $parcels): array
    {
        $responses = [];
        foreach ($parcels as $parcel) {
            $this->setLength($parcel['length']);
            $this->setWidth($parcel['width']);
            $this->setHeight($parcel['height']);
            $this->setWeight($parcel['weight']);
            $this->setUnit($parcel['unit']);
            $response = $this->create();
            if ($response['success']) {
                $responses[] = [
                    'original' => $parcel,
                    'created' => $response['parcel'],
                ];
            }
        }
        return $responses;
    }

    public function create(): array
    {
        try {
            $parcel = $this->makeParcelPayload();
            $client = $this->getClient();
            $response = $client->parcel->create($parcel);

            return [
                'success' => true,
                'message' => 'Parcel created successfully.',
                'parcel' => $this->formatParcelFromResponse($response),
            ];
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function makeParcelPayload(): array
    {
        if ($this->signSize && $this->quantity) {
            if (!str_contains($this->signSize, 'x')) {
                throw new \InvalidArgumentException('Invalid sign size format. Example: 24x36');
            }
            $perSignWeight = 0.5; // in lbs
            $perSignHeight = 0.1; // in inches
            $signSize = explode('x', $this->signSize);
            $this->length = $signSize[0];
            $this->width = $signSize[1];
            $this->height = $this->quantity * $perSignHeight;
            $this->weight = $this->quantity * $perSignWeight;
        }
        if (!$this->length || !$this->width || !$this->height || !$this->weight) {
            throw new \InvalidArgumentException('Length, width, height and weight are required or signSize and quantity are required.');
        }
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->getOunces($this->weight, $this->unit),
        ];
    }

    public function get(string $parcelId): array
    {
        try {
            $client = $this->getClient();
            $response = $client->parcel->retrieve($parcelId);
            return [
                'success' => true,
                'message' => 'Parcel retrieved successfully.',
                'parcel' => $this->formatParcelFromResponse($response),
            ];
        } catch (InvalidRequestException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function formatParcelFromResponse(mixed $response): array
    {
        $data = $response->__toArray();
        return [
            'id' => $data['id'],
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
            'weight' => $data['weight'],
        ];
    }

    public function setSignSize(?string $signSize): void
    {
        $this->signSize = $signSize;
    }

    public function setQuantity(?string $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setLength(?string $length): void
    {
        $this->length = $length;
    }

    public function setWidth(?string $width): void
    {
        $this->width = $width;
    }

    public function setHeight(?string $height): void
    {
        $this->height = $height;
    }

    public function setWeight(?string $weight): void
    {
        $this->weight = $weight;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function getOunces(string $weight, ?string $unit = 'oz'): string
    {
        if ($unit === 'lb') {
            $ounces = $weight * 16;
        } else {
            $ounces = $weight;
        }

        return ceil(round($ounces));
    }

}