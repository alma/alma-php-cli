<?php

namespace App\Command;

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Merchant;
use Alma\API\RequestError;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaMerchantGetCommand extends AbstractAlmaCommand
{
    const FEE_PLANS_TABLE_HEADERS = [
        'cnt',
        'allowed',
        'cust_fee_fix',
        'cust_fee_var',
        'merch_fee_fix',
        'merch_fee_var',
        'min',
        'max',
    ];
    const EXTENDED_DATA_API       = '/v1/me/extended-data';
    const FEE_PLANS_API         = '/v1/me/fee-plans';
    const DEFAULT_TABLE_HEADERS = ['Properties', 'Values'];
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
        $merchant = $this->alma->merchants->me();
        $this->outputMerchant($merchant);
        $this->outputLegalEntity($merchant);

        $this->outputFeePlans($input->getOption('api-fee-plans-source'), $merchant);


        return self::SUCCESS;
    }

    /**
     * @param Merchant $merchant
     */
    protected function outputLegalEntity(Merchant $merchant): void
    {
        $this->io->title('Legal Entity');
        $rows = [];
        foreach ($merchant->legal_entity as $key => $value) {
            if ($key !== 'address') {
                $rows[] = [$key, $value];
            }
        }
        $this->io->table(self::DEFAULT_TABLE_HEADERS, $rows);
    }

    /**
     * @param Merchant $merchant
     */
    protected function outputMerchant(Merchant $merchant)
    {
        $this->io->title('Merchant');
        $rows = [];
        foreach (get_object_vars($merchant) as $property => $value) {
            if (!in_array($property, ['fee_plans', 'legal_entity'])) {
                $rows[] = [$property, $this->formatPrimitive($value)];
            }
        }
        $this->io->table(self::DEFAULT_TABLE_HEADERS, $rows);
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
                    foreach ($merchant->fee_plans as $fee_plan) {
                        $fee_plans[] = $this->populateRowFromArray($fee_plan);
                    }
                    break;
                case self::EXTENDED_DATA_API:
                    foreach ($this->alma->merchants->feePlans() as $feePlan) {
                        $fee_plans[] = $this->populateRowFromObject($feePlan);
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
            $this->formatPrimitive($fee_plan['allowed']),
            $fee_plan['customer_fee_fixed'],
            $fee_plan['customer_fee_variable'],
            $fee_plan['merchant_fee_fixed'],
            $fee_plan['merchant_fee_variable'],
            $fee_plan['min_purchase_amount'],
            $fee_plan['max_purchase_amount'],
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
            $this->formatPrimitive($feePlan->allowed),
            $feePlan->customer_fee_fixed,
            $feePlan->customer_fee_variable,
            $feePlan->merchant_fee_fixed,
            $feePlan->merchant_fee_variable,
            $feePlan->min_purchase_amount,
            $feePlan->max_purchase_amount,
        ];
}
}
