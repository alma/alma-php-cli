<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractWriteAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractWriteAlmaCommand extends AbstractReadAlmaCommand
{

    /**
     * AbstractAlmaCommand constructor.
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->addOption('force-live-env', null, InputOption::VALUE_NONE, 'force execution without interaction if live key is given in ALMA_API_KEY environment');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->initIo($input, $output);
        #TODO: pull down this process in an abstract write sub class
        if (preg_match("#sk_live#", $_ENV['ALMA_API_KEY']) && !$input->getOption('force-live-env')) {
            $this->io->warning("Your ALMA_API_KEY seems to be a LIVE environment API KEY");
            $response = $this->io->choice(
                sprintf("Are you sure you want perform '%s' in LIVE environment ?", $this->getName()),
                ['No', 'Yes'],
                'No'
            );
            if ($response === 'No') {
                $this->io->info(sprintf('command "%s" halted by user', $this->getName()));

                return self::SUCCESS;
            }
        }

        return parent::run($input, $output);
    }

}
