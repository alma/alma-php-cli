<?php

namespace App\Command\Meta;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractGetAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractReadAlmaCommand extends AbstractAlmaCommand implements DisplayOutputInterface, DisplayOutputAddressInterface
{
    use DisplayOutputTrait;
    use DisplayOutputAddressTrait;

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
