<?php

namespace App\Command;

use Alma\API\Client;
use Alma\API\RequestError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaMerchantGetCommand extends Command
{
    protected static $defaultName = 'alma:merchant:get';
    protected static $defaultDescription = 'Get Merchant Informations';
    /** @var Client */
    private $alma;

    /**
     * @param Client $alma
     * @required
     */
    public function setAlma(Client $alma)
    {
        $this->alma = $alma;
    }

    protected function configure(): void
    {
    }

    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump( $this->alma->merchants->me() );

        return Command::SUCCESS;
    }
}
