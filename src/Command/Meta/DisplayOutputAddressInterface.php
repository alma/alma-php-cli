<?php

namespace App\Command\Meta;

/**
 * Interface DisplayOutputAddressInterface
 *
 * @package App\Command\Meta
 */
interface DisplayOutputAddressInterface
{
    const ADDRESSES_KEYS = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'line1',
        'line2',
        'postal_code',
        'city',
        'country',
    ];

    const ADDRESSES_TYPES = [
        'customer',
        'billing',
        'shipping',
    ];
    public function getAddressOptionName(string $type, string $key): string;

    public function outputAddresses(array $addresses, array $additionalFields = []);
}
