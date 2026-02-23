<?php

namespace App\Helper;

class ProductHelper
{
    public static function findLargestSize(array $sizes): string
    {
        $largestSize = '';
        $maxWidth = 0;
        $maxHeight = 0;

        $sizes = array_filter($sizes, function($value) {
            // Regular expression to match the pattern width x height
            return preg_match('/^\d+x\d+$/', $value);
        });
        foreach ($sizes as $size) {
            list($width, $height) = explode('x', $size);

            // Compare width first, then height if widths are equal
            if ($width > $maxWidth || ($width == $maxWidth && $height > $maxHeight)) {
                $maxWidth = $width;
                $maxHeight = $height;
                $largestSize = $size;
            }
        }

        return $largestSize;
    }
}