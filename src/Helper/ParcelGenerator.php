<?php

namespace App\Helper;

use App\Constant\Editor\Addons;

class ParcelGenerator
{
    public const LB_TO_OZ = 16;
    public const PER_SIGN_WEIGHT_LBS = 0.5; // Weight per sign in pounds
    public const PER_SIGN_WEIGHT = 0.5 * self::LB_TO_OZ; // Weight per sign in ounces
    private const PER_SIGN_HEIGHT = 0.1; // Height per sign in inches
    private const PER_SIGN_THICKNESS = 4; // Thickness per sign in mm
    private const PER_STAKE_THICKNESS = 5; // Thickness per stake in mm
    private const PER_SIGN_VALUE = 0.01; // Value per sign in dollars

    private array $boxSizes = [
        ['size' => '18x12x8', 'max_qty' => 50, 'sign_size' => '18x12', 'stakes' => false],
        ['size' => '18x12x16', 'max_qty' => 100, 'sign_size' => '18x12', 'stakes' => false],
        ['size' => '24x24x8', 'max_qty' => 150, 'sign_size' => '18x12', 'stakes' => false],
        ['size' => '24x24x16', 'max_qty' => 200, 'sign_size' => '18x12', 'stakes' => false],

        ['size' => '24x18x6', 'max_qty' => 30, 'sign_size' => '24x18', 'stakes' => false],
        ['size' => '24x18x12', 'max_qty' => 60, 'sign_size' => '24x18', 'stakes' => false],
        ['size' => '24x18x18', 'max_qty' => 115, 'sign_size' => '24x18', 'stakes' => false],
        // ['size' => '24x18x24', 'max_qty' => 140, 'sign_size' => '24x18', 'stakes' => false],

        // only contain stakes
        ['size' => '30x20x4', 'max_qty' => 30, 'sign_size' => '24x18', 'stakes' => false],
        ['size' => '30x20x6', 'max_qty' => 50, 'sign_size' => '24x18', 'stakes' => false],
        ['size' => '30x20x8', 'max_qty' => 75, 'sign_size' => '24x18', 'stakes' => false],
        ['size' => '30x20x12', 'max_qty' => 100, 'sign_size' => '24x18', 'stakes' => false],
        ['size' => '30x24x6', 'max_qty' => 50, 'sign_size' => '24x24', 'stakes' => false],
        ['size' => '30x24x10', 'max_qty' => 80, 'sign_size' => '24x24', 'stakes' => false],

        ['size' => '30x20x4', 'max_qty' => 30, 'sign_size' => '30x20', 'stakes' => true],
        ['size' => '30x20x6', 'max_qty' => 50, 'sign_size' => '30x20', 'stakes' => true],
        ['size' => '30x20x8', 'max_qty' => 75, 'sign_size' => '30x20', 'stakes' => true],
        ['size' => '30x20x12', 'max_qty' => 100, 'sign_size' => '30x20', 'stakes' => true],
        ['size' => '30x24x6', 'max_qty' => 50, 'sign_size' => '30x24', 'stakes' => true],
        ['size' => '30x24x10', 'max_qty' => 80, 'sign_size' => '30x24', 'stakes' => true],

        ['size' => '24x24x8', 'max_qty' => 50, 'sign_size' => '24x24', 'stakes' => false],
        ['size' => '24x24x16', 'max_qty' => 100, 'sign_size' => '24x24', 'stakes' => false],

        ['size' => '36x24x6', 'max_qty' => 30, 'sign_size' => '36x24', 'stakes' => false],
        ['size' => '36x24x12', 'max_qty' => 60, 'sign_size' => '36x24', 'stakes' => false],
        ['size' => '36x24x18', 'max_qty' => 100, 'sign_size' => '36x24', 'stakes' => false],

        ['size' => '48x24x8', 'max_qty' => 10, 'sign_size' => '48x24', 'stakes' => false],
    ];

    /**
     * Choose the most suitable box based on remaining quantity.
     */
    public function chooseBestBox(array $possibleBoxes, int $remainingQty): ?array
    {
        if (empty($possibleBoxes)) {
            return null; // No boxes available
        }

        usort($possibleBoxes, function ($a, $b) {
            return $b['max_qty'] <=> $a['max_qty'];
        });
        foreach ($possibleBoxes as $box) {
            if ($box['max_qty'] >= $remainingQty) {
                return $box;
            }
        }

        return reset($possibleBoxes);
    }


    public function generateDefaultParcels(array $groupedItems): array
    {

        $this->validateGroupedItems($groupedItems);

        $totalSigns = array_sum($groupedItems['sizes']);
        $totalStakes = array_sum($groupedItems['stakes']);
        $useStakesBoxesSigns = ($totalStakes <= 50 && $totalSigns <= 50) && $this->hasStakes($groupedItems['stakes']);

        $parcels = [];

        // Step 1: Find maximum dimensions across all sign sizes
        $maxWidth = 0;
        $maxHeight = 0;
        foreach ($groupedItems['sizes'] as $signSize => $quantity) {
            $dimensions = explode('x', $signSize);
            $width = (int) $dimensions[0];
            $height = (int) $dimensions[1];
            $maxWidth = max($maxWidth, $width);
            $maxHeight = max($maxHeight, $height);
        }

        $virtualSignSize = "$maxWidth x $maxHeight";

        // Step 2: Check if combined quantity allows packing together
        $totalCombined = $totalSigns + $totalStakes;
        if ($totalCombined <= 60) {
            // Try to pack signs and stakes together in sign boxes
            $possibleBoxes = $this->findClosestBoxes($virtualSignSize, $useStakesBoxesSigns);
            if (empty($possibleBoxes)) {
                $possibleBoxes = $this->findClosestBoxes('48x24', false); // Fallback
            }

            $tempQty = $totalCombined;
            $this->createParcelsFromBox($possibleBoxes, $tempQty, $parcels);

            if ($tempQty === 0) {
                return $parcels;
            }
        }

        // Proceed with original packing logic if combined packing failed or not applicable
        // Step 3: Pack signs into selected boxes
        $possibleBoxes = $this->findClosestBoxes($virtualSignSize, $useStakesBoxesSigns);
        if (empty($possibleBoxes)) {
            $possibleBoxes = $this->findClosestBoxes('48x24', false); // Fallback
        }
        $this->createParcelsFromBox($possibleBoxes, $totalSigns, $parcels);

        // Step 4: Pack stakes separately if needed
        if ($totalStakes > 0) {
            $possibleStakesBoxes = array_filter($this->boxSizes, fn($box) => $box['stakes']);
            usort($possibleStakesBoxes, fn($a, $b) => $b['max_qty'] <=> $a['max_qty']);
            $this->createParcelsFromBox($possibleStakesBoxes, $totalStakes, $parcels);
        }

        return $parcels;
    }

    // Modified createParcelsFromBox to accept a parcels array to accumulate results
    private function createParcelsFromBox(array $possibleBoxes, int &$quantity, array &$parcels): void
    {
        while ($quantity > 0) {
            $box = $this->chooseBestBox($possibleBoxes, $quantity);
            if (!$box) {
                break;
            }
            $parcelQuantity = min($box['max_qty'], $quantity);
            $parcels[] = $this->createParcel($box, $parcelQuantity);
            $quantity -= $parcelQuantity;
        }
    }


    private function validateGroupedItems(array $groupedItems): void
    {
        if (!isset($groupedItems['sizes']) || !is_array($groupedItems['sizes'])) {
            throw new \InvalidArgumentException('Invalid grouped items data.');
        }
    }

    private function createParcel(array $box, int $quantity): array
    {
        [$length, $width, $height] = array_map('intval', explode('x', $box['size']));
        $weight = $quantity * self::PER_SIGN_WEIGHT_LBS;

        return [
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'weight' => $weight,
            'value' => $quantity * self::PER_SIGN_VALUE,
            'unit' => 'lb',
            'quantity' => $quantity
        ];
    }

    private function hasStakes(array $stakes): bool
    {
        return !empty(array_filter($stakes, fn($quantity) => $quantity > 0));
    }

    private function findClosestBoxes(string $signSize, bool $useStakesBoxes): array
    {
        $signDimensions = explode('x', $signSize);

        if (count($signDimensions) < 2) {
            return [];
        }

        $signWidth = (int) $signDimensions[0];
        $signHeight = (int) $signDimensions[1];

        // Filter boxes based on whether they match the "stakes" requirement
        $filteredBoxes = array_filter($this->boxSizes, fn($box) => $box['stakes'] === $useStakesBoxes);

        // Only consider boxes that can fit the sign size
        $filteredBoxes = array_filter(
            $filteredBoxes,
            fn($box) => $this->canBoxFit($box, $signWidth, $signHeight)
        );

        // Sort by max_qty in descending order to prioritize larger boxes
        usort($filteredBoxes, fn($a, $b) => $b['max_qty'] <=> $a['max_qty']);

        return $filteredBoxes;
    }


    private function canBoxFit(array $box, int $signWidth, int $signHeight): bool
    {
        $boxDimensions = explode('x', $box['sign_size']);
        $boxWidth = (int) $boxDimensions[0];
        $boxHeight = (int) $boxDimensions[1];

        // A box should have width and height greater than or equal to the sign's width and height
        return intval($boxWidth) >= intval($signWidth) && intval($boxHeight) >= intval($signHeight) || intval($boxWidth) >= intval($signHeight) && intval($boxHeight) >= intval($signWidth);
    }


    private function compareBoxDimensions(array $a, array $b, int $signWidth, int $signHeight): int
    {
        $aDimensions = explode('x', $a['sign_size']);
        $bDimensions = explode('x', $b['sign_size']);
        
        $aWidth = (int) $aDimensions[0];
        $aHeight = (int) $aDimensions[1];
        $bWidth = (int) $bDimensions[0];
        $bHeight = (int) $bDimensions[1];

        $diffA = abs($signWidth - $aWidth) + abs($signHeight - $aHeight);
        $diffB = abs($signWidth - $bWidth) + abs($signHeight - $bHeight);

        return $diffA <=> $diffB;
    }
}
