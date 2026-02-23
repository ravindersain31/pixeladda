<?php 

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class TagsArrayTransformer implements DataTransformerInterface
{
    public function transform($value): string
    {
        return implode(', ', $value);
    }

    public function reverseTransform($value): array
    {
        return array_map('strtolower', array_map('trim', explode(',', $value)));
    }
}
