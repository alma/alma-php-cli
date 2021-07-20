<?php

namespace App\Command;

use Alma\API\Entities\Instalment;
use Alma\API\RequestError;
use App\API\AlmaClient;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AbstractAlmaCommand
 *
 * @package App\Command
 * @TODO split this abstract in 2 read & write classes
 */
abstract class AbstractAlmaCommand extends Command
{
    public const DEFAULT_TABLE_HEADERS = ['Properties', 'Values'];
    protected AlmaClient $alma;
    protected SymfonyStyle $io;

    /**
     * AbstractAlmaCommand constructor.
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name);
        #TODO: pull down this process in an abstract write sub class
        $this->addOption('force-live-env', null, InputOption::VALUE_NONE, 'force execution without interaction if live key is given in ALMA_API_KEY environment');
    }

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
        return (new DateTime())->setTimestamp($timestamp)->format('Y-m-d');
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
                    if (is_iterable($value)) {
                        return $this->implodeWithKeys($value);
                    }

                    return sprintf("%{$keyLength}s%s%s", $key, $keySeparator, $value);
                },
                $array,
                array_keys($array)
            )
        );
    }

    protected function outputAddresses(array $addresses, array $additionalFields = [])
    {
        $rows = [];
        foreach ($addresses as $name => $address) {

            $row = [$name];
            foreach (array_merge($additionalFields, AlmaPaymentCreateCommand::ADDRESSES_KEYS) as $key) {
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
        $this->io->table(array_merge(['NAME'], $additionalFields, AlmaPaymentCreateCommand::ADDRESSES_KEYS), $rows);
    }

    /**
     * @param string $format
     * @param array  $data
     *
     * @return bool
     */
    protected function outputFormat(string $format, array ...$data): bool
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

    /**
     * @param array|Instalment[] $plans
     */
    protected function outputPaymentPlans(array $plans)
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

    protected function outputRequestError(RequestError $exception): int
    {
        $this->io->error($this->formatRequestError($exception));
        print($exception->getTraceAsString());

        return self::FAILURE;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        #TODO: pull down this process in an abstract write sub class
        if (preg_match("#sk_live#", $_ENV['ALMA_API_KEY']) && !$input->getOption('force-live-env')) {
            $this->io->warning("Your ALMA_API_KEY seems to be a LIVE environment API KEY");
            $response = $this->io->choice(sprintf("Are you sure you want perform '%s' in LIVE environment ?", $this->getName()), ['No', 'Yes'], 'No');
            if ($response === 'No') {
                $this->io->info(sprintf('command "%s" halted by user', $this->getName()));
                return self::SUCCESS;
            }
        }
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
        if (empty($value) && !is_int($value)) { // 0 is empty => :facepalm:
            return 'EMPTY';
        }

        return $value;
    }

    protected function outputFormatTable(array ...$data): void
    {
        foreach ($data as $datum) {
            $this->outputKeyValueTable($datum);
        }
    }

    /**
     * @param AlmaClient $alma
     *
     * @required
     */
    public function setAlma(AlmaClient $alma)
    {
        $this->alma = $alma;
    }

}
