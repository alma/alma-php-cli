<?php

namespace App\Command;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\RequestError;
use App\Command\Meta\AbstractReadAlmaCommand;
use App\Command\Meta\DisplayOutputPayloadTrait;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaEligibilityGetCommand extends AbstractReadAlmaCommand
{
    use DisplayOutputPayloadTrait;
    protected static $defaultName = 'alma:eligibility:get';
    protected static $defaultDescription = 'Add a short description for your command';

    /**
     * @param $installment_definition
     *
     * @return array
     * @throws Exception
     */
    protected function buildEligibilityQueryParams($installment_definition): array
    {
        $query = [];
        if (preg_match("#([0-9]+)(-([0-9]+)([mMdD]))?$#", $installment_definition, $match)) {
            $query['installments_count'] = intval($match[1]);
            if (isset($match[4])) {
                switch (strtoupper($match[4])) {
                    case 'D':
                        $query['deferred_days'] = intval($match[3]);
                        break;
                    case 'M':
                        $query['deferred_months'] = intval($match[3]);
                        break;
                }
            }
        }
        if (empty($query)) {
            throw new Exception(sprintf('%s: bad installment definition', $installment_definition));
        }

        return $query;
    }

    /**
     * @param Eligibility $eligibility
     * @param string|int  $cnt
     *
     * @return array
     */
    protected function buildRow(Eligibility $eligibility, $cnt): array
    {
        $reasons        = $eligibility->getReasons();
        $constraints    = $eligibility->getConstraints();
        $purchaseAmount = $constraints['purchase_amount'] ?? null;
        $minimum        = $purchaseAmount ? $purchaseAmount['minimum'] : "";
        $maximum        = $purchaseAmount ? $purchaseAmount['maximum'] : "";
        $eligible       = $eligibility->isEligible() ? "Yes" : "No";
        $plans          = $this->formatEligibility($eligibility);
        $annualRate     = $eligibility->getAnnualInterestRate();

        return [
            $cnt,
            $eligible,
            is_null($annualRate) ? $this->formatPrimitive($annualRate) : $this->formatPercent($annualRate),
            implode("\n", array_map([$this, 'formatPrimitive'], $plans)),
            $reasons ? implode(", ", $reasons) : $this->formatPrimitive($reasons),
            $this->formatMoney($minimum),
            $this->formatMoney($maximum),
        ];
    }

    protected function configure()
    {
        $this->configureOutputPayloadOptions();
        $this
            ->addOption(
                'amount',
                'a',
                InputOption::VALUE_REQUIRED,
                'Set amount in cents to test eligibility',
                10000
            )
            ->addOption(
                'installments',
                'i',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                "Define installments to check (formatted as INSTALLMENT[-DEFERRED]\nwhere INSTALLMENT is an integer\nand where DEFERRED is formatted as integer[MD] for Months or Days",
                ["1-15d", 2, 3, 4, 10]
            )
            ->addOption(
                'raise-on-error',
                'r',
                InputOption::VALUE_NONE,
                'throws Request Exception instead of return not eligible object !'
            )
            ->addOption(
                'payload-file',
                'P',
                InputOption::VALUE_REQUIRED,
                'give payload directly from file instead build it from parameters (give a .php or .json valid file as parameter)'
            )
            ->addOption(
                'api-version',
                'A',
                InputOption::VALUE_REQUIRED,
                'Eligibility version to use',
                1
            )
            ->addOption(
                'shipping-country',
                's',
                InputOption::VALUE_REQUIRED,
                'force shipping_address.country'
            )
            ->addOption(
                'billing-country',
                'b',
                InputOption::VALUE_REQUIRED,
                'force billing_address.country'
            )
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount       = $input->getOption('amount');
        $installments = $input->getOption('installments');
        $version      = intval($input->getOption('api-version'));
        $title        = sprintf(
            'Check Eligibility v%s for %s on following installments [%s]',
            $version,
            $this->formatMoney($amount),
            implode(', ', $installments)
        );
        try {
            $eligibilityData = null;
            $payloadFile     = $input->getOption('payload-file');
            if ($payloadFile) {
                if (!$eligibilityData = $this->getEligibilityFromFile($payloadFile)) {
                    throw new Exception(
                        sprintf("fail to extract eligibility data from '%s':\n%s", $payloadFile, $eligibilityData)
                    );
                }
                $title = sprintf(
                    'Check Eligibility v%s with following payload file %s',
                    $version,
                    $payloadFile
                );
            }
            if (!$eligibilityData) {
                $eligibilityData = $this->getEligibilityData(
                    $amount,
                    $installments,
                    $version,
                    $input->getOption('billing-country') ?: "",
                    $input->getOption('shipping-country') ?: ""
                );
            }
            $this->outputPayload($input, $eligibilityData);
            $eligibilities = $this->almaClient->payments->eligibility(
                $eligibilityData,
                $input->getOption('raise-on-error')
            );
        } catch (RequestError $e) {
            return $this->outputRequestError($e);
        }
        if ($eligibilities instanceof Eligibility) {
            $eligibilities = [$eligibilities];
        }
        $headers = ["Fee Count", "Eligible", "TAEG", "Plans", "Reason", "Min", "Max"];
        $rows    = [];
        foreach ($eligibilities as $cnt => $eligibility) {
            $rows[] = $this->buildRow($eligibility, $cnt);
        }
        $this->io->title($title);
        $this->io->table($headers, $rows);

        return self::SUCCESS;
    }

    /**
     * @param Eligibility $eligibility
     *
     * @return array
     */
    private function formatEligibility(Eligibility $eligibility): array
    {
        $plans = [];
        if (!$paymentPlan = $eligibility->getPaymentPlan()) {
            return [null];
        }
        $barWidth = 0;
        foreach ($paymentPlan as $installment) {
            $planDefinition = sprintf(
                "date:'%s', C_fee:'%10s', interest:'%10s', P_amnt:'%10s', T_amnt:'%10s'",
                $this->formatTimestamp($installment['due_date']),
                $this->formatMoney($installment['customer_fee']),
                $this->formatMoney($installment['customer_interest']),
                $this->formatMoney($installment['purchase_amount']),
                $this->formatMoney($installment['total_amount'])
            );
            $plans[]        = $planDefinition;
            $length         = strlen($planDefinition) - 4; // 2x€ = 6 chars instead 2
            $barWidth       = $length > $barWidth ? $length : $barWidth;
        }
        $plans[] = str_repeat("-", $barWidth);


        return $plans;
    }

    /**
     * @param int    $amount
     * @param array  $installments
     * @param int    $version
     * @param string $billingCountry
     * @param string $shippingCountry
     *
     * @return array[]
     * @throws Exception
     */
    protected function getEligibilityData(
        int $amount,
        array $installments,
        int $version,
        string $billingCountry = "",
        string $shippingCountry = ""
    ): array {
        switch ($version) {
            case 1:
                return [
                    'payment' => [
                        'purchase_amount'    => $amount,
                        'shipping_address'   => ['country' => $shippingCountry],
                        'billing_address'    => ['country' => $billingCountry],
                        'installments_count' => array_map(
                            function ($installment) {
                                return intval($installment);
                            },
                            $installments
                        ),
                    ],
                ];
            case 2:
                $queries = [];
                foreach ($installments as $installment_definition) {
                    $queries[] = $this->buildEligibilityQueryParams($installment_definition);
                }

                return [
                    'purchase_amount'  => $amount,
                    'shipping_address' => ['country' => $shippingCountry],
                    'billing_address'  => ['country' => $billingCountry],
                    'queries'          => $queries,
                ];


            default:
                throw new Exception(sprintf('version %s : WTF !!!', $version));
        }
    }

    /**
     * @param null|string $payloadFile
     *
     * @return null|array
     */
    private function getEligibilityFromFile(?string $payloadFile): ?array
    {
        if (!$payloadFile) {
            return null;
        }
        if (!file_exists($payloadFile)) {
            $this->io->warning(sprintf('payloadFile %s not found', $payloadFile));

            return null;
        }
        if (preg_match("#.php$#", $payloadFile)) {
            return require($payloadFile);
        }
        if (preg_match("#.json$#", $payloadFile)) {
            return json_decode(file_get_contents($payloadFile), true);
        }
        $this->io->warning(
            sprintf('payloadFile %s is not a valid file ext (only .json & .php are allowed)', $payloadFile)
        );

        return null;
    }
}
