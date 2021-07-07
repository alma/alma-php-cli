<?php

namespace App\Command;

use Alma\API\Client;
use Alma\API\RequestError;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AbstractAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractAlmaCommand extends Command
{
    public const DEFAULT_TABLE_HEADERS = ['Properties', 'Values'];
    protected Client $alma;
    protected SymfonyStyle $io;

    /**
     * @param RequestError $exception
     *
     * @return string
     */
    protected function formatRequestError(RequestError $exception): string
    {
        $stringErrors = "";
        if (isset($exception->response->json['errors'])) {

            foreach ($exception->response->json['errors'] as $error) {
                $stringErrors .= $this->implodeWithKeys($error);
            }
        }

        return sprintf(
            "(%s) : %s (%s)\n%s",
            $exception->response->errorMessage,
            $exception->response->responseCode,
            $exception->response->json['error_code'] ?? 'No error code',
            $stringErrors ?: "No json errors"
        );
    }

    /**
     * @param int $timestamp
     *
     * @return string
     */
    protected function formatTimestamp(int $timestamp): string
    {
        return (new DateTime())->setTimestamp(intval($timestamp))->format('Y-m-d');
    }

    /**
     * @param array  $array
     * @param string $separator
     * @param string $keySeparator
     * @param int    $keyLength
     *
     * @return string
     */
    protected function implodeWithKeys(array $array, string $separator = "\n", string $keySeparator = ": ", int $keyLength = 15): string
    {
        return implode(
            $separator,
            array_map(
                function ($value, $key) use ($keySeparator, $keyLength) {

                    return sprintf("%{$keyLength}s%s%s", $key, $keySeparator, $value);
                },
                $array,
                array_keys(
                    $array
                )
            )
        );
    }

    /**
     * @param iterable $iterable
     * @param array    $excludedProperties
     */
    protected function outputKeyValueTable(iterable $iterable, array $excludedProperties = []): void
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
            if ($key === 'created') {
                $rows[] = [$key, $this->formatTimestamp($value)];
                continue;
            }
            $rows[] = [$key, $this->formatPrimitive($value)];
        }
        $this->io->table(self::DEFAULT_TABLE_HEADERS, $rows);
    }

    protected function outputRequestError(RequestError $exception): int
    {
        $this->io->error($this->formatRequestError($exception));
        print($exception->getTraceAsString());

        return self::FAILURE;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        return parent::run($input, $output);
    }

    protected function formatMoney($amount = 0): string
    {
        return sprintf("%.2f €", round(intval($amount) / 100, 2));
    }

    /**
     * Format bool / null or empty values
     *
     * @param $value
     *
     * @return string
     */
    protected function formatPrimitive($value): string
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        if (empty($value)) {
            return 'EMPTY';
        }

        return $value;
    }

    /**
     * @param Client $alma
     * @required
     */
    public function setAlma(Client $alma)
    {
        $this->alma = $alma;
    }

}
