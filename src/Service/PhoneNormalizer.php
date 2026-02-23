<?php

namespace App\Service;

class PhoneNormalizer
{
    /**
     * Normalize a phone number string by removing all non-digit characters.
     */
    public function normalize(string $phone): ?string
    {
        return preg_replace('/\D+/', '', $phone);
    }

    /**
     * Returns SQL expression that normalizes a phone field in MySQL.
     * Works for ANY phone format.
     */
    public function getNormalizedSql(string $field = 'u.phone'): string
    {
        return "
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE($field, '(', ''),
                    ')', ''),
                '-', ''),
            ' ', '')
        ";
    }
}
