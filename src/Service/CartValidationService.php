<?php

namespace App\Service;

class CartValidationService
{
    public function validate(array $data): array|bool
    {
        $productType = $data['productType'] ?? ['quantityType' => 'BY_SIZES'];
        $items = $data['items'] ?? [];
        $quantity = $this->validateQuantity($items, $productType);
        if ($quantity !== true) {
            return $quantity;
        }

        $dataValidation = $this->validateEditorData($items, $productType);
        if ($dataValidation !== true) {
            return $dataValidation;
        }
        return true;
    }

    public function validateQuantity(array $items, array $productType): array|bool
    {
        $totalQty = 0;
        foreach ($items as $item) {
            $totalQty += $item['quantity'];
        }
        if (count($items) === 0 || $totalQty <= 0) {
            $isQuantityBased = isset($productType['quantityType']) && $productType['quantityType'] == 'BY_QUANTITY';
            return [
                'action' => 'error',
                'message' => $isQuantityBased ? 'Please enter qty' :'Please select a size',
                'moveTo' => 'choose-your-sizes'
            ];
        }
        return true;
    }

    public function validateEditorData(array $items, array $productType): array|bool
    {

        $isQuantityBased = isset($productType['quantityType']) && $productType['quantityType'] == 'BY_QUANTITY';
        $isYardLetters = isset($productType['quantityType']) && $productType['slug'] == 'yard-letters';
        $isWireStake = isset($productType['quantityType']) && $productType['slug'] == 'wire-stake';

        if($isQuantityBased || $isYardLetters || $isWireStake) {
            return true;
        }
        foreach ($items as $item) {
            if ($item['quantity'] > 0) {
                $isCustom = $item['isCustom'];

                // skip wire stake
                if (isset($item['isWireStake']) && $item['isWireStake']) {
                    continue;
                }

                // skip sample
                if (isset($item['isSample']) && $item['isSample']) {
                    continue;
                }

                // skip blank signs
                if (isset($item['isBlankSign']) && $item['isBlankSign']) {
                    continue;
                }

                if ($item['canvasData']) {
                    if ($item['canvasData']['front'] == null || (is_array($item['canvasData']['front']) && count($item['canvasData']['front']) <= 0)) {
                        if ($isCustom) {
                            continue;
                        }
                        return [
                            'action' => 'error',
                            'message' => ($isCustom ? 'Upload custom design for ' : 'Missing data for ') . $item['name'],
                            'moveTo' => 'choose-design-option'
                        ];
                    }
                    if ($item['addons']['sides']['key'] === 'DOUBLE') {
                        if ($item['canvasData']['back'] == null || (is_array($item['canvasData']['back']) && count($item['canvasData']['back']) <= 0)) {
                            if ($isCustom) {
                                continue;
                            }
                            return [
                                'action' => 'error',
                                'message' => ($isCustom ? 'Upload custom design for backside of ' : 'Missing data for backside of ') . $item['name'],
                                'moveTo' => 'choose-design-option'
                            ];
                        }
                    }
                } else {
                    return [
                        'action' => 'error',
                        'message' => 'Product is not initialized. Please refresh the page.'
                    ];
                }
            }
        }
        return true;
    }
}