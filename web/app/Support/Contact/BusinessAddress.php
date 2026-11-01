<?php

declare(strict_types=1);

namespace App\Support\Contact;

/**
 * The studio address is written once in config/site.php as parts, so the page
 * and schema.org read the same values. Two copies of an address always drift.
 */
final class BusinessAddress
{
    /**
     * The address as it is printed: street, locality, then region + postal code.
     *
     * @param  array<string, string>  $address
     * @return list<string>
     */
    public static function lines(array $address): array
    {
        $region = trim(($address['region'] ?? '').' '.($address['postal_code'] ?? ''));

        return array_values(array_filter([
            trim($address['street'] ?? ''),
            trim($address['locality'] ?? ''),
            $region,
        ]));
    }

    /**
     * @param  array<string, string>  $address
     * @return array<string, string>
     */
    public static function postalAddress(array $address): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => trim($address['street'] ?? ''),
            'addressLocality' => trim($address['locality'] ?? ''),
            'addressRegion' => trim($address['region'] ?? ''),
            'postalCode' => trim($address['postal_code'] ?? ''),
            'addressCountry' => trim($address['country'] ?? ''),
        ]);
    }
}
