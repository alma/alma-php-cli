<?php

namespace App\Command\Meta;

use App\Formatter\FormatterTrait;
use InvalidArgumentException;

/**
 * Trait DisplayOutputAddressTrait
 *
 * @package App\Command\Meta
 */
trait DisplayOutputAddressTrait
{
    use FormatterTrait;

    public function getAddressOptionName(string $type, string $key): string
    {
        if (!in_array($type, DisplayOutputAddressInterface::ADDRESSES_TYPES)) {
            throw new InvalidArgumentException(sprintf('%s: BAD ADDRESS TYPE', $type));
        }

        return sprintf('%s-%s', $type, str_replace("_", "-", $key));
    }

    public function outputAddresses(array $addresses, array $additionalFields = [])
    {
        $rows = [];
        foreach ($addresses as $name => $address) {

            $row = [$name];
            foreach (array_merge($additionalFields, DisplayOutputAddressInterface::ADDRESSES_KEYS) as $key) {
                if (!isset($address[$key])) {
                    $row[] = 'UNDEFINED';
                    continue;
                }
                if ($key === 'created') {
                    $row[] = $this->formatTimestamp($address[$key]);
                    continue;
                }
                $row[] = $this->formatPrimitive($address[$key]);
            }
            $rows[] = $row;
        }
        $this->io->table(array_merge(['NAME'], $additionalFields, DisplayOutputAddressInterface::ADDRESSES_KEYS), $rows);
    }
}
