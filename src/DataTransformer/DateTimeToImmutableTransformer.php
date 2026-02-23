<?php

namespace App\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimeToImmutableTransformer implements DataTransformerInterface
{
    public function transform($value): ?\DateTime
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTimeImmutable) {
            throw new TransformationFailedException('Expected a \DateTimeImmutable object.');
        }

        return new \DateTime($value->format('Y-m-d H:i:s'), $value->getTimezone());
    }

    public function reverseTransform($value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTime) {
            throw new TransformationFailedException('Expected a \DateTime object.');
        }

        return \DateTimeImmutable::createFromMutable($value);
    }
}
