<?php

namespace App\Command\Meta;

use Alma\API\Entities\Instalment;
use Alma\API\RequestError;
use App\Formatter\FormatterTrait;
use Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Trait DisplayOutputTrait
 *
 * @package App\Command
 */
trait DisplayOutputTrait
{
    use FormatterTrait;

    /**
     * @param mixed  $data
     * @param string $outputFormat
     *
     * @throws Exception
     */
    public function output($data, string $outputFormat = 'table')
    {
        switch ($outputFormat) {
            case DisplayOutputInterface::OUTPUT_TABLE:
                $this->outputKeyValueTable($data);
                break;
            case DisplayOutputInterface::OUTPUT_JSON:
                printf("%s\n", json_encode($data, JSON_PRETTY_PRINT));
                break;
            default:
                throw new Exception(sprintf('%s: WTF output format !!!', $outputFormat));

        }
    }

    /**
     * @param string $format
     * @param array  $data
     *
     * @return bool
     */
    public function outputFormat(string $format, array ...$data): bool
    {
        switch ($format) {
            case 'var_export':
                var_export($data);
                break;
            case 'var_dump':
                var_dump($data);
                break;
            case 'dump':
                dump($data);
                break;
            case 'json':
                print(json_encode($data, JSON_PRETTY_PRINT));
                break;
            case 'table':
                $this->outputFormatTable($data);
                break;
            default:
                $this->io->error(sprintf('%s: not a valid format', $format));

                return false;
        }

        return true;
    }

    public function outputFormatTable(array ...$data): void
    {
        foreach ($data as $datum) {
            $this->outputKeyValueTable($datum);
        }
    }

    /**
     * @param iterable $iterable
     * @param array    $excludedProperties
     */
    public function outputKeyValueTable(iterable $iterable, array $excludedProperties = []): void
    {
        $rows = [];
        foreach ($iterable as $key => $value) {
            if (in_array($key, $excludedProperties)) {
                continue;
            }
            if (is_array($value)) {
                $rows[] = [$key, $this->implodeWithKeys($value)];
                continue;
            }
            if (in_array($key, ['created', 'updated', 'available_on'])) {
                $rows[] = [$key, $this->formatTimestamp($value)];
                continue;
            }
            $rows[] = [$key, $this->formatPrimitive($value)];
        }
        $this->io->table(DisplayOutputInterface::DEFAULT_TABLE_HEADERS, $rows);
    }

    /**
     * @param array|Instalment[] $plans
     */
    public function outputPaymentPlans(array $plans)
    {
        $rows    = [];
        $headers = [
            'state',
            'purchase_amount',
            'original_purchase_amount',
            'due_date',
            'customer_fee',
            'id',
            'customer_can_postpone',
        ];
        foreach ($plans as $plan) {
            $rows[] = [
                $plan->state,
                $this->formatMoney($plan->purchase_amount),
                $this->formatMoney($plan->original_purchase_amount),
                $this->formatMoney($plan->original_purchase_amount),
            ];
        }
        $this->io->table($headers, $rows);
    }

    public function outputRequestError(RequestError $exception): int
    {
        $this->io->error($this->formatRequestError($exception));
        print($exception->getTraceAsString());

        return Command::FAILURE;
    }
}
