<?php

namespace App\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractGetAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractReadAlmaCommand extends AbstractAlmaCommand
{
    const OUTPUT_JSON  = 'json';
    const OUTPUT_TABLE = 'table';

    const AVAILABLE_OUTPUTS = [
        self::OUTPUT_TABLE,
        self::OUTPUT_JSON,
    ];

    /**
     * @param mixed  $data
     * @param string $outputFormat
     *
     * @throws Exception
     */
    protected function output($data, string $outputFormat = 'table')
    {
        switch ($outputFormat) {
            case self::OUTPUT_TABLE:
                $this->outputKeyValueTable($data);
                break;
            case self::OUTPUT_JSON:
                printf("%s\n", json_encode($data, JSON_PRETTY_PRINT));
                break;
            default:
                throw new Exception(sprintf('%s: WTF output format !!!', $outputFormat));

        }
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            sprintf('output format (valid options are [%s])', implode(', ', self::AVAILABLE_OUTPUTS)),
            self::OUTPUT_TABLE
        );

        return parent::run($input, $output);
    }
}
