<?php

namespace App\Command;

use Alma\API\RequestError;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $io = new SymfonyStyle($input, $output);
        $merchant = $this->alma->merchants->me();
        $headers = ['Id', 'Name', 'Website', 'Plans'];
        $rows[] = [
            $merchant->id,
            $merchant->name,
            $merchant->website,
            implode("\n", $this->formatFeePlans($merchant->fee_plans)),
        ];
        $io->table($headers, $rows);

        return self::SUCCESS;
    }

    private function formatFeePlans(array $feePlans): array
    {
        $plans = [];
        foreach ($feePlans as $feePlan) {
            $plans[] = sprintf(
                "cnt:'%sx', allowed:'%s',\n\tcust_fee_fix:'%s', cust_fee_var:'%s',\n\tmerch_fee_fix:'%s', merch_fee_var:'%s',\n\tmin:'%s', max:'%s'",
                $feePlan['installments_count'],
                $feePlan['allowed'] ? 'Yes' : 'No',
                $feePlan['customer_fee_fixed'],
                $feePlan['customer_fee_variable'],
                $feePlan['merchant_fee_fixed'],
                $feePlan['merchant_fee_variable'],
                $feePlan['min_purchase_amount'],
                $feePlan['max_purchase_amount']
            );
        }

        return $plans;
    }
}
