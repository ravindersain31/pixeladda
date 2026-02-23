<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Helper\ParcelGenerator;
use InvalidArgumentException;

class ParcelGeneratorTest extends TestCase
{
    private ParcelGenerator $parcelGenerator;

    protected function setUp(): void
    {
        $this->parcelGenerator = new ParcelGenerator();
    }

    public function testGenerateDefaultParcelsWithValidInput(): void
    {
        $groupedItems = [
            'sizes' => [
                '24x18' => 50,
                '18x12' => 30,
            ],
            'stakes' => [],
        ];

        $parcels = $this->parcelGenerator->generateDefaultParcels($groupedItems);

        $this->assertIsArray($parcels);
        $this->assertNotEmpty($parcels);
        $this->assertArrayHasKey('length', $parcels[0]);
        $this->assertArrayHasKey('width', $parcels[0]);
        $this->assertArrayHasKey('height', $parcels[0]);
        $this->assertArrayHasKey('weight', $parcels[0]);
        $this->assertArrayHasKey('value', $parcels[0]);
    }

    public function testGenerateDefaultParcelsWithStakes(): void
    {
        $groupedItems = [
            'sizes' => [
                '24x18' => 40,
            ],
            'stakes' => ['FRAME_WIRE_STAKE_10X30' => 10],
        ];

        $parcels = $this->parcelGenerator->generateDefaultParcels($groupedItems);

        $this->assertIsArray($parcels);
        $this->assertNotEmpty($parcels);

        foreach ($parcels as $parcel) {
            $this->assertArrayHasKey('length', $parcel);
            $this->assertArrayHasKey('width', $parcel);
            $this->assertArrayHasKey('height', $parcel);
            $this->assertArrayHasKey('weight', $parcel);
            $this->assertArrayHasKey('value', $parcel);
        }
    }

    public function testGenerateDefaultParcelsWithInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $groupedItems = [
            'wrong_key' => [
                '24x18' => 50,
            ],
        ];

        $this->parcelGenerator->generateDefaultParcels($groupedItems);
    }

    public function testGenerateDefaultParcelsWithZeroQuantity(): void
    {
        $groupedItems = [
            'sizes' => [
                '24x18' => 0,
            ],
            'stakes' => [],
        ];

        $parcels = $this->parcelGenerator->generateDefaultParcels($groupedItems);

        $this->assertIsArray($parcels);
        $this->assertEmpty($parcels);
    }
}
