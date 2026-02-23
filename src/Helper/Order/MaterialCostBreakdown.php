<?php
namespace App\Helper\Order;

use App\Constant\CogsConstant;
use App\Constant\Editor\Addons;
use App\Helper\PriceChartHelper;

use function PHPUnit\Framework\isNull;

class MaterialCostBreakdown
{
    private $orderItems;
    private $precuts;
    private $sheets;
    private $stakes;
    private $inkCost;
    private $sheetsUsed;
    private $sheetsCost;
    private $containerCostPrecuts;
    private $containerCostFullSheets;
    private $wireStakeUsed;
    private $wireStakeCost;
    private $totalWireStakeUsed;
    private $containerCostWireStakes;
    private $sheetsSingleSidedPrint;
    private $sheetsDoubleSidedPrint;
    private $totalBoxCost;
    private $totalBoxesUsed;
    private $totalMaterialCost;
    private $materialCostBreakdown;
    private $totalSignsPrinted;
    private $totalLaborCost;
    private array $sizes = [];
    private float $totalQuantity = 0;

    public function __construct($orderItems)
    {
        $this->orderItems = $orderItems;
        $this->calculateMaterialCost();
    }

    private function calculateMaterialCost()
    {
        // Initialize variables
        $this->sheetsSingleSidedPrint = 0;
        $this->sheetsDoubleSidedPrint = 0;
        $this->sheetsUsed = 0;
        $this->totalWireStakeUsed = 0;
        $this->wireStakeUsed = [];
        $signsPrintedPerSheet = [
            '36x24' => 4,
            '24x24' => 8,
            '24x18' => 10,
            '18x24' => 8,
            '18x12' => 16,
            '12x18' => 20,
            '12x12' => 32,
            '9x24' => 20,
            '9x12' => 40,
            '6x24' => 32,
            '6x18' => 40
        ];
        $this->sheetsCost = 0;
        $this->containerCostPrecuts = 0;
        $this->containerCostFullSheets = 0;
        $this->containerCostWireStakes = 0;
        $this->totalSignsPrinted = 0;
        $this->totalLaborCost = 0;

        // Arrays for different sheet and stake categories
        $this->precuts = [
            '18x12' => 0,
            '24x18' => 0,
            '18x24' => 0,
            '24x24' => 0
        ];
        $this->stakes = [
            CogsConstant::TYPE_WIRE_STAKE_10X30_SINGLE => 0,
            CogsConstant::TYPE_WIRE_STAKE_10X30 => 0,
            CogsConstant::TYPE_WIRE_STAKE_10X24 => 0,
            CogsConstant::TYPE_WIRE_STAKE_10X30_PREMIUM => 0,
            CogsConstant::TYPE_WIRE_STAKE_10X24_PREMIUM => 0
        ];

        // Initialize variables for full sheets
        $this->sheets['fullSheetsUsed'] = 0;

        // Process each order item
        foreach ($this->orderItems as $orderItem) {
            if ($orderItem->getItemType() === 'DEFAULT') {
                $product = $orderItem->getProduct();
                $variantName = $product->getName();
                $quantity = $orderItem->getQuantity();

                // Handle wire stakes
                if ($orderItem->isWireStake()) {
                    $stakeType = $orderItem->getProduct()->getName() ?? CogsConstant::TYPE_WIRE_STAKE_10X24;
                    $this->wireStakeUsed[$stakeType] = ($this->wireStakeUsed[$stakeType] ?? 0) + $quantity;
                    $this->stakes[$stakeType] += $quantity;
                    $this->containerCostWireStakes += $quantity * CogsConstant::STAKES_CONTAINER_COST;
                    continue;
                }

                // Handle custom sizes or variants
                if (!isset($signsPrintedPerSheet[$variantName]) || $orderItem->getProduct()->isIsCustomSize()) {
                    $customSize = $orderItem->getMetaDataKey('customSize');
                    $variant = is_array($customSize)
                        ? $customSize['templateSize']['width'] . 'x' . $customSize['templateSize']['height']
                        : $variantName;
                    $variantName = PriceChartHelper::getClosestVariant($variant, array_keys($signsPrintedPerSheet));
                }

                if (!isset($signsPrintedPerSheet[$variantName]) || $orderItem->getProduct()->isIsCustomSize()) {
                    $customSize = $orderItem->getMetaDataKey('customSize');
                    $customVariant = is_array($customSize)
                        ? $customSize['templateSize']['width'] . 'x' . $customSize['templateSize']['height']
                        : $variantName;
                    $this->sizes[$customVariant] = ($this->sizes[$customVariant] ?? 0) + $quantity;
                }else {
                    $this->sizes[$variantName] = ($this->sizes[$variantName] ?? 0) + $quantity;
                }

                // Determine sheet cost and container cost
                $sheetCostPerItem = CogsConstant::FULL_SHEET_COST;
                $containerCostPerItem = CogsConstant::FULL_SHEET_CONTAINER_COST;

                if ($variantName === '24x18') {
                    $sheetCostPerItem = CogsConstant::PRE_CUT_SHEET_24x18_COST;
                    $containerCostPerItem = CogsConstant::PRE_CUT_CONTAINER_COST;
                    $this->precuts['24x18'] += $quantity;
                } elseif ($variantName === '18x12') {
                    $sheetCostPerItem = CogsConstant::PRE_CUT_SHEET_18x12_COST;
                    $containerCostPerItem = CogsConstant::PRE_CUT_CONTAINER_COST;
                    $this->precuts['18x12'] += $quantity;
                } elseif ($variantName === '18x24') {
                    $sheetCostPerItem = CogsConstant::PRE_CUT_SHEET_18x24_COST;
                    $containerCostPerItem = CogsConstant::PRE_CUT_CONTAINER_COST;
                    $this->precuts['18x24'] += $quantity;
                } elseif ($variantName === '24x24') {
                    $sheetCostPerItem = CogsConstant::PRE_CUT_SHEET_24x24_COST;
                    $containerCostPerItem = CogsConstant::PRE_CUT_CONTAINER_COST;
                    $this->precuts['24x24'] += $quantity;
                }

                // Calculate sheets used and associated costs
                $sheetUsedForThisItem = $quantity;
                
                if ($variantName !== '18x12' && $variantName !== '24x18' && $variantName !== '18x24' && $variantName !== '24x24') {
                    $sheetUsedForThisItem = (1 / $signsPrintedPerSheet[$variantName]) * $quantity;
                    $this->sheetsUsed += $sheetUsedForThisItem;
                    $this->sheets['fullSheetsUsed'] += $sheetUsedForThisItem;
                    $this->sheetsCost += $sheetUsedForThisItem * CogsConstant::FULL_SHEET_COST;
                    $this->containerCostFullSheets += $sheetUsedForThisItem * CogsConstant::FULL_SHEET_CONTAINER_COST;
                } else {
                    $this->sheetsCost += ceil($sheetUsedForThisItem) * $sheetCostPerItem;
                    $this->containerCostPrecuts += $quantity * $containerCostPerItem;
                }

                // Accumulate total signs printed
                $this->totalSignsPrinted += $quantity;
                $this->totalQuantity += $quantity;

                // Handle sides (single/double)
                $addOns = $orderItem->getAddOns();
                $sides = $addOns['sides'] ?? [];
                if (isset($sides['key']) && $sides['key'] === 'SINGLE') {
                    $this->sheetsSingleSidedPrint += $quantity;
                } else {
                    $this->sheetsDoubleSidedPrint += $quantity;
                }

                // Handle wire frames
                $wireFrame = $addOns['frame'] ?? [];
                if (isset($wireFrame['key']) && $wireFrame['key'] !== 'NONE') {
                    $stakeType = $wireFrame['key'];
                    $this->wireStakeUsed[$stakeType] = ($this->wireStakeUsed[$stakeType] ?? 0) + $quantity;

                    if (isset($this->stakes[$stakeType])) {
                        $this->stakes[$stakeType] += $quantity;
                    } else {
                        $this->stakes[$stakeType] = $quantity;
                    }
                    $this->containerCostWireStakes += $quantity * CogsConstant::STAKES_CONTAINER_COST;
                }
            }
        }

        // Calculate ink cost
        $this->inkCost = ($this->sheetsSingleSidedPrint * CogsConstant::INK_COST_SINGLE_SIDED) +
            ($this->sheetsDoubleSidedPrint * CogsConstant::INK_COST_DOUBLE_SIDED);

        // Calculate wire stake cost
        $this->wireStakeCost = 0;
        $this->totalWireStakeUsed = array_sum($this->wireStakeUsed);

        foreach ($this->wireStakeUsed as $stakeType => $stakeQuantity) {
            $this->wireStakeCost += $stakeQuantity * CogsConstant::getStakeCost($stakeType);
        }

        $this->totalLaborCost = ($this->sheetsSingleSidedPrint * CogsConstant::LABOR_COST_SINGLE_SIDED) + ($this->sheetsDoubleSidedPrint * CogsConstant::LABOR_COST_DOUBLE_SIDED);

        // Calculate box cost per sign
        $this->totalBoxesUsed = ceil($this->totalSignsPrinted / CogsConstant::SIGNS_PER_BOX);
        $this->totalBoxCost = $this->totalBoxesUsed * CogsConstant::BOX_COST;

        // Total material cost
        $this->totalMaterialCost = $this->sheetsCost + $this->containerCostPrecuts + $this->containerCostFullSheets + $this->containerCostWireStakes +
            $this->inkCost + $this->wireStakeCost + $this->totalBoxCost;

        // Build the table rows
        $this->materialCostBreakdown = $this->generateMaterialCostBreakdown();
    }

    private function generateMaterialCostBreakdown(): array
    {
        $tableRows = [];

        // Sheets Section
        if ($this->precuts['18x12'] > 0) {
            $tableRows[] = [
                'item' => 'Precut 18x12',
                'quantity' => $this->precuts['18x12'],
                'unitCost' => CogsConstant::PRE_CUT_SHEET_18x12_COST,
                'totalCost' => $this->precuts['18x12'] * CogsConstant::PRE_CUT_SHEET_18x12_COST,
            ];
        }
        if ($this->precuts['24x18'] > 0) {
            $tableRows[] = [
                'item' => 'Precut 24x18',
                'quantity' => $this->precuts['24x18'],
                'unitCost' => CogsConstant::PRE_CUT_SHEET_24x18_COST,
                'totalCost' => $this->precuts['24x18'] * CogsConstant::PRE_CUT_SHEET_24x18_COST,
            ];
        }

        if ($this->precuts['18x24'] > 0) {
            $tableRows[] = [
                'item' => 'Precut 18x24',
                'quantity' => $this->precuts['18x24'],
                'unitCost' => CogsConstant::PRE_CUT_SHEET_18x24_COST,
                'totalCost' => $this->precuts['18x24'] * CogsConstant::PRE_CUT_SHEET_18x24_COST,
            ];
        }

        if ($this->precuts['24x24'] > 0) {
            $tableRows[] = [
                'item' => 'Precut 24x24',
                'quantity' => $this->precuts['24x24'],
                'unitCost' => CogsConstant::PRE_CUT_SHEET_24x24_COST,
                'totalCost' => $this->precuts['24x24'] * CogsConstant::PRE_CUT_SHEET_24x24_COST,
            ];
        }

        // Container Cost Section
        if ($this->containerCostPrecuts > 0) {
            $tableRows[] = [
                'item' => 'Container Cost (Precuts)',
                'quantity' => array_sum($this->precuts),
                'unitCost' => CogsConstant::PRE_CUT_CONTAINER_COST,
                'totalCost' => $this->containerCostPrecuts,
            ];
        }

        // if ($this->sheets['fullSheetsUsed'] > 0) {
        //     $tableRows[] = [
        //         'item' => 'Full Sheets',
        //         'quantity' => $this->sheets['fullSheetsUsed'],
        //         'unitCost' => CogsConstant::FULL_SHEET_COST,
        //         'totalCost' => $this->sheets['fullSheetsUsed'] * CogsConstant::FULL_SHEET_COST,
        //     ];
        // }

        if ($this->sheetsUsed > 0) {
            $tableRows[] = [
                'item' => 'Full Sheets',
                'quantity' => $this->sheetsUsed,
                'unitCost' => CogsConstant::FULL_SHEET_COST,
                'totalCost' => $this->sheetsUsed * CogsConstant::FULL_SHEET_COST,
            ];
        }

        if ($this->containerCostFullSheets > 0) {
            $tableRows[] = [
                'item' => 'Container Cost (Full Sheets)',
                'quantity' => $this->sheets['fullSheetsUsed'],
                'unitCost' => CogsConstant::FULL_SHEET_CONTAINER_COST,
                'totalCost' => $this->containerCostFullSheets,
            ];
        }

        // Ink Section
        if ($this->sheetsSingleSidedPrint > 0) {
            $inkTotalCostSS = $this->sheetsSingleSidedPrint * CogsConstant::INK_COST_SINGLE_SIDED;
            $tableRows[] = [
                'item' => 'Ink (SS)',
                'quantity' => $this->sheetsSingleSidedPrint,
                'unitCost' => CogsConstant::INK_COST_SINGLE_SIDED,
                'totalCost' => $inkTotalCostSS,
            ];
        }

        if ($this->sheetsDoubleSidedPrint > 0) {
            $inkTotalCostDS = $this->sheetsDoubleSidedPrint * CogsConstant::INK_COST_DOUBLE_SIDED;
            $tableRows[] = [
                'item' => 'Ink (DS)',
                'quantity' => $this->sheetsDoubleSidedPrint,
                'unitCost' => CogsConstant::INK_COST_DOUBLE_SIDED,
                'totalCost' => $inkTotalCostDS,
            ];
        }


        // Wire Stakes Section
        foreach ($this->wireStakeUsed as $stakeType => $stakeQuantity) {

            if ($stakeQuantity === 0) {
                continue;
            }

            $stakeUnitCost = CogsConstant::getStakeCost($stakeType);
            $totalStakeCost = $stakeQuantity * $stakeUnitCost;
            $tableRows[] = [
                'item' => ucfirst(Addons::getFrameDisplayText($stakeType)),
                'quantity' => $stakeQuantity,
                'unitCost' => $stakeUnitCost,
                'totalCost' => $totalStakeCost,
            ];
        }

        // Wire Stake Container Cost Section
        if ($this->wireStakeCost > 0) {
            $tableRows[] = [
                'item' => 'Container Cost (Wire Stakes)',
                'quantity' => $this->totalWireStakeUsed,
                'unitCost' => CogsConstant::STAKES_CONTAINER_COST,
                'totalCost' => $this->totalWireStakeUsed * CogsConstant::STAKES_CONTAINER_COST,
            ];
        }

        // Boxes Section
        if ($this->totalSignsPrinted > 0) {
            $tableRows[] = [
                'item' => 'Box Cost',
                'quantity' => $this->totalBoxesUsed,
                'unitCost' => CogsConstant::BOX_COST,
                'totalCost' => $this->totalBoxCost,
            ];
        }

        // Total Row
        $calculatedTotal = array_sum(array_column($tableRows, 'totalCost'));
        $tableRows[] = [
            'item' => 'Total',
            'quantity' => '',
            'unitCost' => '',
            'totalCost' => $calculatedTotal,
        ];

        return $tableRows;
    }

    public function getPrecuts(): array
    {
        return $this->precuts;
    }

    public function getSheets(): array
    {
        return array_merge($this->sheets, $this->precuts);
    }

    public function getStakes(): array
    {
        return $this->stakes;
    }

    public function getInkCost(): float
    {
        return $this->inkCost;
    }

    public function getSheetsUsed(): float
    {
        return $this->sheetsUsed;
    }

    public function getTotalSheetsUsed(): float
    {
        return $this->sheetsUsed + $this->precuts['24x18'] + $this->precuts['18x12'] + $this->precuts['18x24'] + $this->precuts['24x24'];
    }

    public function getSheetsCost(): float
    {
        return $this->sheetsCost;
    }

    public function getContainerCostPrecuts(): float
    {
        return $this->containerCostPrecuts;
    }

    public function getContainerCostFullSheets(): float
    {
        return $this->containerCostFullSheets;
    }

    public function getWireStakeUsed(): array
    {
        return $this->wireStakeUsed;
    }

    public function getWireStakeCost(): float
    {
        return $this->wireStakeCost;
    }

    public function getTotalWireStakeUsed(): float
    {
        return $this->totalWireStakeUsed;
    }

    public function getSheetsSingleSidedPrint(): float
    {
        return $this->sheetsSingleSidedPrint;
    }

    public function getSheetsDoubleSidedPrint(): float
    {
        return $this->sheetsDoubleSidedPrint;
    }

    public function getTotalBoxCost(): float
    {
        return $this->totalBoxCost;
    }

    public function getTotalMaterialCost(): float
    {
        return $this->totalMaterialCost;
    }

    public function getMaterialCostBreakdown(): array
    {
        return $this->materialCostBreakdown;
    }

    public function getTotalSignsPrinted(): float
    {
        return $this->totalSignsPrinted;
    }

    public function getTotalLaborCost(): float
    {
        return $this->totalLaborCost;
    }

    public function getSizes(): array
    {
        return $this->sizes;
    }

    public function getTotalQuantity(): float
    {
        return $this->totalQuantity;
    }
}