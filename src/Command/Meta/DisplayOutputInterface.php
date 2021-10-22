<?php

namespace App\Command\Meta;

use Alma\API\RequestError;

/**
 * Interface DisplayOutputInterface
 *
 * @package App\Command\Meta
 */
interface DisplayOutputInterface
{

    public const AVAILABLE_OUTPUTS     = [
        self::OUTPUT_TABLE,
        self::OUTPUT_JSON,
    ];
    public const OUTPUT_TABLE          = 'table';
    public const OUTPUT_JSON           = 'json';
    public const DEFAULT_TABLE_HEADERS = ['Properties', 'Values'];

    public function output($data, string $outputFormat = 'table');
    public function outputFormat(string $format, array ...$data): bool;
    public function outputFormatTable(array ...$data): void;
    public function outputKeyValueTable(iterable $iterable, array $excludedProperties = []): void;
    public function outputPaymentPlans(array $plans);
    public function outputRequestError(RequestError $exception): int;

}
