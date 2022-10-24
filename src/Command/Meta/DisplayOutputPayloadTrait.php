<?php

namespace App\Command\Meta;

use App\Command\Meta\Exception\OutputFormatException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Trait DisplayOutputPayloadTrait
 *
 * @package App\Command\Meta
 */
trait DisplayOutputPayloadTrait
{
    protected function configureOutputPayloadOptions()
    {
        $this->addOption(
            'output-payload',
            'p',
            InputOption::VALUE_NONE,
            'should I output payload before create payment'
        )
            ->addOption(
                'format-payload',
                'f',
                InputOption::VALUE_OPTIONAL,
                'should I format output payload (works with --output-payload) - possible values are table, dump, json, var_dump, var_export',
                'table'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param array          $data
     *
     * @return void
     * @throws OutputFormatException
     */
    protected function outputPayload(InputInterface $input, array ...$data): void
    {
        if ($input->getOption('output-payload')) {
            $outputFormat = $input->getOption('format-payload');
            if (!$this->outputFormat($outputFormat, $data)) {
                throw new OutputFormatException("unable to format output-payload with format '$outputFormat'");
            }
        }
    }

}
