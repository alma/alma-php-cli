<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Endpoints\AlmaBase;
use DateTime;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaAccountingTransactionsGetCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:accounting-transactions:get';
    protected static $defaultDescription = 'Call /v1/accounting/transactions endpoint';

    /**
     * @throws RequestError
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $endpoint = new AlmaBase($this->almaClient->getContext());
        $this->io->title('/v1/accounting/transactions');
        $request = $endpoint->request('/v1/accounting/transactions');
        $queryParams = [];
        if ($from = $input->getOption('from')) {
            $queryParams['created[min]'] = (new DateTime($from))->getTimestamp();
        }
        if ($to = $input->getOption('to')) {
            $queryParams['created[max]'] = (new DateTime($to))->getTimestamp();
        }
        if ($queryParams) {
            $request->setQueryParams($queryParams);
        }
        foreach ($request->get()->json['data'] as $key => $datum) {
            $this->outputKeyValueTable(array_merge(['key' => $key], $datum));
        }
        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED, 'from date as YYYY-MM-DD [HH:mm:ss]')
            ->addOption('to', 't', InputOption::VALUE_REQUIRED, 'to date as YYYY-MM-DD [HH:mm:ss]')
        ;
    }
}
