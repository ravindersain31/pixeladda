<?php

namespace App\Helper\Order;

use App\Constant\CogsConstant;
use App\Constant\Editor\Addons;
// NOTE: Precuts is the number of 18x12, 18x24, 24x24 and 24x18 sheets
// NOTE: Sheets is the number of full sheets

class MonthlyMaterialCostBreakdown
{
    private array $orders;
    private array $precuts = [
        '18x12' => 0,
        '24x18' => 0,
        '18x24' => 0,
        '24x24' => 0
    ];
    private float $sheetsUsed = 0;
    private array $sheets = [];

    private float $sheetsCost = 0;
    private float $inkCost = 0;
    private float $containerCostPrecuts = 0;
    private float $containerCostFullSheets = 0;
    private array $wireStakes = [];
    private float $wireStakeUsed = 0;
    private float $wireStakeCost = 0;
    private float $totalWireStakeUsed = 0;
    private float $containerCostWireStakes = 0;
    private float $sheetsSingleSidedPrint = 0;
    private float $sheetsDoubleSidedPrint = 0;
    private float $totalBoxCost = 0;
    private int $totalBoxesUsed = 0;
    private float $totalSignsPrinted = 0;
    private float $totalMaterialCost = 0;
    private array $materialCostBreakdown = [];
    private float $totalLaborCost = 0;

    public function __construct(array $orders)
    {
        $this->orders = $orders;
        $this->initializeWireStakeCounts();
        $this->calculateMaterialCost();
    }

    private function initializeWireStakeCounts(): void
    {
        // Initialize wire stake counts for each type
        foreach (CogsConstant::getStakeTypes() as $stakeType) {
            $this->wireStakes[$stakeType] = 0;
        }
    }

    private function calculateMaterialCost(): void
    {
        $this->sheets['fullSheetsUsed'] = 0;

        foreach ($this->orders as $order) {
            $materialCost = $order->getMaterialCost();
            // Aggregate sheet usage and costs
            $this->sheetsSingleSidedPrint += $materialCost['sheetsSingleSidedPrint'] ?? 0;
            $this->sheetsDoubleSidedPrint += $materialCost['sheetsDoubleSidedPrint'] ?? 0;
            $this->sheetsUsed += $materialCost['sheetsUsed'] ?? 0;
            $this->sheetsCost += $materialCost['sheetsCost'] ?? 0;

            // Calculate total Labor Cost
            $this->totalLaborCost += $materialCost['totalLaborCost'] ?? 0;

            // Aggregate ink cost
            $this->inkCost += $materialCost['inkCost'] ?? 0;
            $this->sheets['fullSheetsUsed'] += $materialCost['sheets']['fullSheetsUsed'] ?? 0;

            // Aggregate total signs printed
            $this->totalSignsPrinted += $materialCost['totalSignsPrinted'] ?? 0;

            // Aggregate wire stakes
            if (isset($materialCost['wireStakes'])) {
                foreach ($this->wireStakes as $stakeType => $count) {
                    $this->wireStakes[$stakeType] += $materialCost['wireStakes'][$stakeType] ?? 0;
                    $this->wireStakeUsed += $materialCost['wireStakes'][$stakeType] ?? 0;
                    $this->totalWireStakeUsed += $materialCost['wireStakes'][$stakeType] ?? 0;
                }
            }

            // Aggregate precut sheets
            if (isset($materialCost['precuts'])) {
                $this->precuts['18x12'] += $materialCost['precuts']['18x12'] ?? 0;
                $this->precuts['24x18'] += $materialCost['precuts']['24x18'] ?? 0;
                $this->precuts['18x24'] += $materialCost['precuts']['18x24'] ?? 0;
                $this->precuts['24x24'] += $materialCost['precuts']['24x24'] ?? 0;
            }
        }

        // Calculate wire stake cost
        $this->wireStakeCost = 0;

        foreach ($this->wireStakes as $stakeType => $count) {
            $this->wireStakeCost += $count * CogsConstant::getStakeCost($stakeType);
            $this->containerCostWireStakes += $count * CogsConstant::STAKES_CONTAINER_COST;
        }

        // Calculate box cost per sign
        $this->totalBoxesUsed = ceil($this->totalSignsPrinted / CogsConstant::SIGNS_PER_BOX);
        $this->totalBoxCost = $this->totalBoxesUsed * CogsConstant::BOX_COST;

        // Calculate precut sheets cost
        $precutsCost = 0;
        $precutsCost += $this->precuts['18x12'] * CogsConstant::PRE_CUT_SHEET_18x12_COST;
        $precutsCost += $this->precuts['24x18'] * CogsConstant::PRE_CUT_SHEET_24x18_COST;
        $precutsCost += $this->precuts['18x24'] * CogsConstant::PRE_CUT_SHEET_18x24_COST;
        $precutsCost += $this->precuts['24x24'] * CogsConstant::PRE_CUT_SHEET_24x24_COST;

        // Calculate container costs
        $this->containerCostPrecuts = array_sum($this->precuts) * CogsConstant::PRE_CUT_CONTAINER_COST;

        // sheetused is full sheets
        // precuts are 18x12, 18x24, 24x24 and 24x18

        $this->containerCostFullSheets = $this->sheetsUsed * CogsConstant::FULL_SHEET_CONTAINER_COST;

        // Calculate total material cost
        $this->totalMaterialCost = $this->sheetsCost + $this->inkCost + $this->wireStakeCost + $this->totalBoxCost + $this->containerCostPrecuts + $this->containerCostFullSheets + $this->containerCostWireStakes;

        // Generate material cost breakdown
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
                'quantity' => $this->sheetsUsed,
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
        foreach ($this->wireStakes as $stakeType => $stakeQuantity) {
            if ($stakeQuantity <= 0) {
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

        // container Cost Wire Stakes Section
        if ($this->containerCostWireStakes > 0) {
            $tableRows[] = [
                'item' => 'Container Cost (Wire Stakes)',
                'quantity' => array_sum($this->wireStakes),
                'unitCost' => CogsConstant::STAKES_CONTAINER_COST,
                'totalCost' => $this->containerCostWireStakes,
            ];
        }

        // Boxes per sign Section
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

    public function getSheetsUsed(): float
    {
        return $this->sheetsUsed;
    }

    public function getTotalSheetsUsed(): float
    {
        return $this->sheetsUsed + $this->precuts['18x12'] + $this->precuts['24x18'] + $this->precuts['18x24'] + $this->precuts['24x24'];
    }

    public function getTotalSignsPrinted(): float
    {
        return $this->totalSignsPrinted;
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
        return $this->wireStakes;
    }

    public function getWireStakeCost(): float
    {
        return $this->wireStakeCost;
    }

    public function getTotalWireStakeUsed(): float
    {
        return $this->wireStakeUsed;
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

    public function getInkCost(): array
    {
        return [
            'sheetsSingleSidedPrint' => $this->sheetsSingleSidedPrint,
            'sheetsDoubleSidedPrint' => $this->sheetsDoubleSidedPrint,
            'inkCostDoubleSided' => CogsConstant::INK_COST_DOUBLE_SIDED,
            'inkCostSingleSided' => CogsConstant::INK_COST_SINGLE_SIDED,
            'inkCost' => $this->inkCost,
        ];
    }

    public function getSheets(): array
    {
        return [
            'sheetsUsed' => ($this->sheetsUsed),
            'sheetsUsedActual' => $this->sheetsUsed,
            'singleSheetCost' => CogsConstant::FULL_SHEET_COST,
            'sheetsCost' => $this->sheetsCost,
            'precuts' => [
                '18x12' => $this->precuts['18x12'],
                '24x18' => $this->precuts['24x18'],
                '18x24' => $this->precuts['18x24'],
                '24x24' => $this->precuts['24x24']
            ],
            'fullSheetsUsed' => $this->sheets['fullSheetsUsed'],
            'sheetsData' => $this->sheets + $this->precuts
        ];
    }

    public function getTotalLaborCost(): float
    {
        return $this->totalLaborCost;
    }
}
