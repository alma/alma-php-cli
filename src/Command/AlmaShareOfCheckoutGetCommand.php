<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Command\Meta\AbstractReadAlmaCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaShareOfCheckoutGetCommand extends AbstractReadAlmaCommand
{
    protected static $defaultName = 'alma:share-of-checkout:get';
    protected static $defaultDescription = 'Get last update from share of checkout endpoint';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            dump($this->almaClient->shareOfCheckout->getLastUpdateDates());
        } catch (RequestError $e) {
            dump([
                $e->getMessage(),
                $e->getCode(),
                $e->response->responseCode,
            ]);
        }

        return Command::SUCCESS;
    }
}
