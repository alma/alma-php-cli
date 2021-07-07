<?php

namespace App\Command;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\RequestError;
use DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaEligibilityGetCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:eligibility:get';
    protected static $defaultDescription = 'Add a short description for your command';

    protected function configure()
    {
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
                'Define installments to check',
                [2, 3, 4, 10]
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount        = $input->getOption('amount');
        $installments        = $input->getOption('installments');
        try {
            $eligibilities = $this->alma->payments->eligibility(
                [
                    'payment' => [
                        'purchase_amount'    => $amount,
                        'installments_count' => array_map(
                            function ($installment) {
                                return intval($installment);
                            },
                            $installments
                        ),
                    ],
                ]
            );
        } catch (RequestError $e) {
            return $this->outputRequestError($e);
        }
        $headers       = ["Fee Count", "Eligible", "Plans", "Reason", "Min", "Max"];
        $rows          = [];
        foreach ($eligibilities as $cnt => $eligibility) {
            $reasons        = $eligibility->getReasons();
            $constraints    = $eligibility->getConstraints();
            $purchaseAmount = $constraints['purchase_amount'] ?? null;
            $minimum        = $purchaseAmount ? $purchaseAmount['minimum'] : "";
            $maximum        = $purchaseAmount ? $purchaseAmount['maximum'] : "";
            $eligible       = $eligibility->isEligible() ? "Yes" : "No";
            $plans          = $this->formatEligibility($eligibility);
            $rows[]         = [
                $cnt . "x",
                $eligible,
                implode("\n", $plans),
                $reasons ? implode(", ", $reasons) : "",
                $this->formatMoney($minimum),
                $this->formatMoney($maximum),
            ];
        }
        $this->io->title(sprintf('Check Eligibility for %s on following installments [%s]', $this->formatMoney($amount), implode(', ', $installments)));
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
        if ($paymentPlans = $eligibility->getPaymentPlan()) {
            $barWidth = 0;
            foreach ($paymentPlans as $paymentPlan) {
                $planDefinition = sprintf(
                    "date:'%s', fee:'%10s', amount:'%s'",
                    $this->formatTimestamp($paymentPlan['due_date']),
                    $this->formatMoney($paymentPlan['customer_fee']),
                    $this->formatMoney($paymentPlan['purchase_amount'])
                );
                $plans[]        = $planDefinition;
                $length         = strlen($planDefinition) - 4; // 2x€ = 6 chars instead 2
                $barWidth       = $length > $barWidth ? $length : $barWidth;
            }
            $plans[] = str_repeat("-", $barWidth);

        }

        return $plans;
    }

}
