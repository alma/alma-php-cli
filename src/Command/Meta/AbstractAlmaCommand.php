<?php

namespace App\Command\Meta;

use App\API\AlmaClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AbstractAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractAlmaCommand extends Command
{
    public const INPUT_OPTION_REQUIRED_ARRAY = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED;
    protected AlmaClient $almaClient;
    protected ?SymfonyStyle $io = null;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initIo(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->io) {
            $this->io = new SymfonyStyle($input, $output);
        }
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->initIo($input, $output);

        return parent::run($input, $output);
    }

    /**
     * @param AlmaClient $almaClient
     *
     * @required
     */
    public function setAlmaClient(AlmaClient $almaClient)
    {
        $this->almaClient = $almaClient;
    }

}
