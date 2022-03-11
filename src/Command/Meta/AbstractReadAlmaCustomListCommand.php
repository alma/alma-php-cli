<?php

namespace App\Command\Meta;

use Alma\API\RequestError;
use App\Endpoints\AlmaBase;
use DateTime;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractReadAlmaCustomListCommand
 *
 * @package App\Command\Meta
 */
abstract class AbstractReadAlmaCustomListCommand extends AbstractReadAlmaCommand
{
    const CUSTOM_ENDPOINT_URI = 'TO REPLACE BY VALID ALMA API LIST ENDPOINT';

    /**
     * A method to override if you want override default params to send to GET request.
     *
     * @param array          $queryParams
     * @param InputInterface $input
     *
     * @return array
     */
    protected function bindParams(array $queryParams, InputInterface $input): array
    {
        return $queryParams;
    }

    /**
     * @throws RequestError
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $customEndpoint = new AlmaBase($this->almaClient->getContext());
        $this->io->title(static::CUSTOM_ENDPOINT_URI);
        $request = $customEndpoint->request(static::CUSTOM_ENDPOINT_URI);
        $queryParams = [];
        if ($from = $input->getOption('from')) {
            $queryParams['created[min]'] = (new DateTime($from))->getTimestamp();
        }
        if ($to = $input->getOption('to')) {
            $queryParams['created[max]'] = (new DateTime($to))->getTimestamp();
        }
        $request->setQueryParams($this->bindParams($queryParams, $input));
        foreach ($request->get()->json['data'] as $key => $datum) {
            $this->output(array_merge(['key' => $key], $datum), $input->getOption('output'));
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
