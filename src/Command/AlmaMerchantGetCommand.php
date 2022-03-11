<?php

namespace App\Command;

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Merchant;
use Alma\API\RequestError;
use App\Command\Meta\AbstractReadAlmaCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaMerchantGetCommand extends AbstractReadAlmaCommand
{
    const FEE_PLANS_TABLE_HEADERS = [
        'cnt',
        'kind',
        'allowed',
        'deferred',
        'trigger_in',
        'cust_fee_fix',
        'cust_fee_var',
        'cust_lend_rate',
        'merch_fee_fix',
        'merch_fee_var',
        'min',
        'max',
    ];
    const EXTENDED_DATA_API       = '/v1/me/extended-data';
    const FEE_PLANS_API         = '/v1/me/fee-plans';
    protected static $defaultName = 'alma:merchant:get';
    protected static $defaultDescription = 'Get Merchant Informations';

    protected function configure(): void
    {
        $this->addOption(
            'api-fee-plans-source',
            'f',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Choose from which API endpoint you want get fee plans information',
            [self::EXTENDED_DATA_API, self::FEE_PLANS_API]
        );
    }

    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $merchant = $this->almaClient->merchants->me();
        $this->outputMerchant($merchant);
        $this->outputLegalEntity($merchant);

        $this->outputFeePlans($input->getOption('api-fee-plans-source'), $merchant);


        return self::SUCCESS;
    }

    private function formatDeferred(int $deferred_days, int $deferred_months): string
    {
        $formatted = "";
        if ($deferred_days > 0) {
            $formatted = sprintf("%s days", $deferred_days);
        }
        if ($deferred_months > 0) {
            if ($formatted) {
                // should not be occurs because deferred months & days are mutually exclusives
                $formatted .= " + ";
            }
            $formatted .= sprintf("%s months", $deferred_months);
        }

        return $formatted ?: 'NONE';
    }

    /**
     * @param Merchant $merchant
     */
    protected function outputLegalEntity(Merchant $merchant): void
    {
        $this->io->title('Legal Entity');
        $this->outputKeyValueTable($merchant->legal_entity, ['address']);
    }

    /**
     * @param Merchant $merchant
     */
    protected function outputMerchant(Merchant $merchant)
    {
        $this->io->title('Merchant');
        $this->outputKeyValueTable(get_object_vars($merchant), ['fee_plans', 'legal_entity']);
    }

    /**
     * @param array    $sources
     * @param Merchant $merchant
     *
     * @throws RequestError
     */
    protected function outputFeePlans(array $sources, Merchant $merchant): void
    {
        foreach ($sources as $source) {
            $fee_plans = [];
            $this->io->title(sprintf('fee plans from %s', $source));
            switch ($source) {
                case self::FEE_PLANS_API:
                    foreach ($this->almaClient->merchants->feePlans(FeePlan::KIND_GENERAL, 'all', true) as $feePlan) {
                        $fee_plans[] = $this->populateRowFromObject($feePlan);
                    }
                    break;
                case self::EXTENDED_DATA_API:
                    foreach ($merchant->fee_plans as $fee_plan) {
                        $fee_plans[] = $this->populateRowFromArray($fee_plan);
                    }
                    break;
                default:
            }
            $this->io->table(self::FEE_PLANS_TABLE_HEADERS, $fee_plans);
        }
    }

    /**
     * @param array $fee_plan
     *
     * @return array
     */
    protected function populateRowFromArray(array $fee_plan): array
    {
        return [
            $fee_plan['installments_count'],
            $this->formatPrimitive($fee_plan['kind']),
            $this->formatPrimitive($fee_plan['allowed']),
            $this->formatDeferred($fee_plan['deferred_days'], $fee_plan['deferred_months']),
            $this->formatPrimitive($fee_plan['deferred_trigger_limit_days']),
            $this->formatMoney($fee_plan['customer_fee_fixed']),
            $this->formatPercent($fee_plan['customer_fee_variable']),
            $this->formatPercent($fee_plan['customer_lending_rate']),
            $this->formatMoney($fee_plan['merchant_fee_fixed']),
            $this->formatPercent($fee_plan['merchant_fee_variable']),
            $this->formatMoney($fee_plan['min_purchase_amount']),
            $this->formatMoney($fee_plan['max_purchase_amount']),
        ];
}

    /**
     * @param FeePlan $feePlan
     *
     * @return array
     */
    protected function populateRowFromObject(FeePlan $feePlan): array
    {
        return [
            $feePlan->installments_count,
            $this->formatPrimitive($feePlan->kind),
            $this->formatPrimitive($feePlan->allowed),
            $this->formatDeferred($feePlan->deferred_days, $feePlan->deferred_months),
            $this->formatPrimitive($feePlan->deferred_trigger_limit_days),
            $this->formatMoney($feePlan->customer_fee_fixed),
            $this->formatPercent($feePlan->customer_fee_variable),
            $this->formatPercent($feePlan->customer_lending_rate),
            $this->formatMoney($feePlan->merchant_fee_fixed),
            $this->formatPercent($feePlan->merchant_fee_variable),
            $this->formatMoney($feePlan->min_purchase_amount),
            $this->formatMoney($feePlan->max_purchase_amount),
        ];
}
}
