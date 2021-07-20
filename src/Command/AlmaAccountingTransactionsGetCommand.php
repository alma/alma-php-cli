<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Endpoints\AlmaBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaAccountingTransactionsGetCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:accounting-transactions:get';
    protected static $defaultDescription = 'Call /v1/accounting/transactions endpoint';

    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $endpoint = new AlmaBase($this->alma->getContext());
        $this->io->title('/v1/accounting/transactions');
        foreach ($endpoint->request('/v1/accounting/transactions')->get()->json['data'] as $key => $datum) {
            $this->outputKeyValueTable(array_merge(['key' => $key], $datum));
        }
        return Command::SUCCESS;
    }
}
