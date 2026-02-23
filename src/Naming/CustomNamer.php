<?php

namespace App\Naming;

use App\Entity\Category;
use App\Entity\Product;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Naming\Polyfill;

class CustomNamer implements NamerInterface
{
    use Polyfill\FileExtensionTrait;

    public function name(object $object, PropertyMapping $mapping): string
    {
        $file = $mapping->getFile($object);
        $name = \str_replace('.', '', \uniqid('', true));
        $extension = $this->getExtension($file);

        if (\is_string($extension) && '' !== $extension) {
            $name = \sprintf('%s.%s', $name, $extension);
        }

        if ($object instanceof Product) {
            $product = $object->getParent();
            if ($product instanceof Product) {
                $sku = $product->getSku();
                $variantName = $object->getName();
                $name = $sku . '/' . $variantName . '_' . $name;
            } else {
                $sku = $object->getSku();
                $name = $sku . '/' . $name;
            }
            return $name;
        }

        if ($object instanceof Category) {
            $name = $object->getSlug() . '/' . $name;
        }

        return $name;
    }
}