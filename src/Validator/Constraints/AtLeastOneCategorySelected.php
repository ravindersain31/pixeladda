<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AtLeastOneCategorySelected extends Constraint
{
    public string $message = 'At least one category should be selected.';
}
