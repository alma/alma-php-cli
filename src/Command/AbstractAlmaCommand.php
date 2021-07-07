<?php

namespace App\Command;

use Alma\API\Client;
use Alma\API\RequestError;
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
    protected Client $alma;
    protected SymfonyStyle $io;

    /**
     * @param RequestError $exception
     *
     * @return string
     */
    protected function formatRequestError(RequestError $exception): string
    {
        $return = "";
        foreach ($exception->response->json['errors'] as $error) {
            $return .= implode(
                "\n",
                array_map(
                    function ($value, $key) {

                        return sprintf("%15s: %s", $key, $value);
                    },
                    $error,
                    array_keys(
                        $error
                    )
                )
            );
        }

        return sprintf(
            "(%s) : %s\n%s",
            $exception->response->responseCode,
            $exception->response->json['error_code'],
            $return
        );
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
