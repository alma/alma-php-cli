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
        $rows = [];
        foreach (get_object_vars($merchant) as $property => $value) {
            if (!in_array($property, ['fee_plans', 'legal_entity'])) {
                $rows[] = [$property, $this->formatValue($value)];
            }
        }

        $io->table(['Properties', 'Values'], $rows);
        $fee_plans = [];
        foreach ($merchant->fee_plans as $fee_plan) {

            $fee_plans[] = [
                $fee_plan['installments_count'],
                $fee_plan['allowed'] ? 'Yes' : 'No',
                $fee_plan['customer_fee_fixed'],
                $fee_plan['customer_fee_variable'],
                $fee_plan['merchant_fee_fixed'],
                $fee_plan['merchant_fee_variable'],
                $fee_plan['min_purchase_amount'],
                $fee_plan['max_purchase_amount'],
            ];
        }
        $fee_plans_headers = [
            'cnt',
            'allowed',
            'cust_fee_fix',
            'cust_fee_var',
            'merch_fee_fix',
            'merch_fee_var',
            'min',
            'max',
        ];
        $io->table($fee_plans_headers, $fee_plans);


        return self::SUCCESS;
    }

    private function formatFeePlans(array $feePlans): array
    {
        $plans = [];
        foreach ($feePlans as $feePlan) {
            $plans[] = sprintf(
                "cnt:'%sx', allowed:'%s',\n\tcust_fee_fix:'%s', cust_fee_var:'%s',\n\tmerch_fee_fix:'%s', merch_fee_var:'%s',\n\tmin:'%s', max:'%s'",
            );
        }

        return $plans;
    }

    private function formatValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        if (empty($value)) {
            return 'EMPTY';
        }

        return $value;
    }
}
