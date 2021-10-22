<?php

namespace App\Command;

use Alma\API\Endpoints\Payments;
use Alma\API\RequestError;
use App\Command\Meta\AbstractReadAlmaCommand;
use App\Endpoints\AlmaBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaPaymentListCommand extends AbstractReadAlmaCommand
{
    protected static $defaultName = 'alma:payment:list';
    protected static $defaultDescription = 'Retrieve list of payments (since payment ID if given)';

    protected function configure()
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'limit result list',
                10
            )
            ->addOption(
                'starting-after-payment-id',
                's',
                InputOption::VALUE_REQUIRED,
                'the payment id formatted as payment_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx or only xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx if you prefer'
            )
        ;
    }


    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $endpoint    = new AlmaBase($this->almaClient->getContext());
        $request     = $endpoint->request(Payments::PAYMENTS_PATH);
        $queryParams = [];
        if ($limit = $input->getOption('limit')) {
            $queryParams['limit'] = $limit;
        }
        if ($paymentId = $input->getOption('starting-after-payment-id')) {
            $queryParams['starting_after'] = $paymentId;
        }
        if ($queryParams) {
            $request->setQueryParams($queryParams);
        }

        $headers = [
            'idx',
            'ID',
            'left_to_pay',
            'amount',
            'state',
            'count',
            'days',
            'months',
            'origin',
            'created',
            'updated',
        ];
        $this->io->table($headers, $this->formatPayments($request->get()->json['data']));

        return Command::SUCCESS;
    }

    /**
     * @param array $payments
     *
     * @return array
     */
    private function formatPayments(array $payments): array
    {
        $rows = [];
        $cnt = 0;
        foreach ($payments as $payment) {
            $rows[] = [
                '#'.$cnt,
                $payment['id'],
                $this->formatMoney($payment['purchase_amount']),
                $this->formatMoney($payment['amount_left_to_pay']),
                $payment['state'],
                // TODO count only plans with purchase_amount !== 0 (if pay later, first payment === 0)
                count($payment['payment_plan']),
                $payment['deferred_days'],
                $payment['deferred_months'],
                $payment['origin'],
                $this->formatTimestamp($payment['created']),
                $this->formatTimestamp($payment['updated']),
            ];
            $cnt++;
        }
        return $rows;
    }
}
