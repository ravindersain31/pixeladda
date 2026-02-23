<?php

namespace App\DataTransformer;

use App\Entity\Country;
use App\Entity\State;
use Symfony\Component\Form\DataTransformerInterface;

class AddressFormCountryStateTransformer implements DataTransformerInterface
{

    public function transform(mixed $value): mixed
    {
        return $value;
    }

    public function reverseTransform(mixed $value): array
    {
        $transformed = $value;
        $country = $value['country'] ?? null;
        if ($country instanceof Country) {
            $transformed = [
                ...$value,
                'country' => $country->getIsoCode(),
            ];
        }
        $state = $value['state'] ?? null;
        if ($state instanceof State) {
            $transformed = [
                ...$transformed,
                'state' => $state->getIsoCode(),
            ];
        }
        return $transformed;
    }
}