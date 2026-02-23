<?php

namespace App\Helper;

class AddressHelper
{

    public static function getFullAddress($address): string
    {
        $fullAddress = $address['firstName'];
        $fullAddress .= ' ' . $address['lastName'];

        $fullAddress .= ', Address Line 1: ' . $address['addressLine1'];
        if ($address['addressLine2']) {
            $fullAddress .= ', Address Line 2: ' . $address['addressLine2'];
        }
        $fullAddress .= ', City: ' . $address['city'];
        $fullAddress .= ', State: ' . $address['state'];
        $fullAddress .= ', Zipcode: ' . $address['zipcode'];
        $fullAddress .= ', Country: ' . $address['country'];
        $fullAddress .= ' (EMail: ' . $address['email'];
        $fullAddress .= ' | Phone: ' . $address['phone'] . ')';

        return $fullAddress;
    }

    public static function formatAddressBlock(array $address): string
    {
        $lines = [];
        $lines[] = $address['firstName'] . ' ' . $address['lastName'];
        $lines[] = $address['addressLine1'];
        if (!empty($address['addressLine2'])) {
            $lines[] = $address['addressLine2'];
        }
        $lines[] = $address['city'] . ', ' . $address['state'] . ' ' . $address['zipcode'];
        $lines[] = $address['country'];
        $lines[] = 'Email: ' . $address['email'];
        $lines[] = 'Phone: ' . $address['phone'];

        return implode("\n", $lines);
    }

    public static function formatAddressChange(array $old, array $new): string
    {
        return "*Previous Address:*\n"
            . self::formatAddressBlock($old)
            . "\n\n*New Address:*\n"
            . self::formatAddressBlock($new);
    }

    public static function isAddressUpdated(array $oldAddress, array $newAddress): bool
    {
        $keys = ['firstName', 'lastName', 'addressLine1', 'addressLine2', 'city', 'state', 'zipcode', 'country', 'phone'];

        foreach ($keys as $key) {
            $oldValue = isset($oldAddress[$key]) ? trim($oldAddress[$key]) : null;
            $newValue = isset($newAddress[$key]) ? trim($newAddress[$key]) : null;

            if ($oldValue !== $newValue) {
                return true;
            }
        }

        return false;
    }
}