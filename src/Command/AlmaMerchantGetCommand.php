<?php

namespace App\Command;

use Alma\API\RequestError;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaMerchantGetCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:merchant:get';
    protected static $defaultDescription = 'Get Merchant Informations';

    protected function configure(): void
    {
    }

    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump( $this->alma->merchants->me() );

        return self::SUCCESS;
    }
}
