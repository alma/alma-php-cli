<?php

namespace App\Command;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\RequestError;
use DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AlmaEligibilityGetCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:eligibility:get';
    protected static $defaultDescription = 'Add a short description for your command';

    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $eligibilities = $this->alma->payments->eligibility(['payment' => [
            'purchase_amount' => 10000,
            'installments_count' => [
                2,3,4,5,10,11,
            ]
        ]]);
        $headers = ["Fee Count", "Eligible", "Plans", "Reason", "Min", "Max"];
        $rows = [];
        foreach ($eligibilities as $cnt => $eligibility) {
            $reasons        = $eligibility->getReasons();
            $constraints    = $eligibility->getConstraints();
            $purchaseAmount = $constraints['purchase_amount'] ?? null;
            $minimum        = $purchaseAmount ? $purchaseAmount['minimum'] : "";
            $maximum        = $purchaseAmount ? $purchaseAmount['maximum'] : "";
            $eligible       = $eligibility->isEligible() ? "Yes" : "No";
            $plans          = $this->formatPlans($eligibility);
            $rows[]         = [
                $cnt."x",
                $eligible,
                implode("\n", $plans),
                $reasons ? implode(", ", $reasons) : "",
                $this->formatMoney($minimum),
                $this->formatMoney($maximum),
            ];
            if (!$eligibility->isEligible()) {
                dump($eligibility);
            }

        }
        $io->table($headers, $rows);

        return self::SUCCESS;
    }

    private function formatMoney($amount = 0): string
    {
        return sprintf("%.2f €", round(intval($amount)/100, 2));
    }

    /**
     * @param Eligibility $eligibility
     *
     * @return array
     */
    private function formatPlans(Eligibility $eligibility): array
    {
        $plans = [];
        if ($paymentPlans = $eligibility->getPaymentPlan()) {
            $date = new DateTime();
            foreach ($paymentPlans as $paymentPlan) {
                $date->setTimestamp(intval($paymentPlan['due_date']));
                $plans[] = sprintf("date:'%s', fee:'%s', amount:'%s'",
                    $date->format('Y-m-d'),
                    $this->formatMoney($paymentPlan['customer_fee']),
                    $this->formatMoney($paymentPlan['purchase_amount'])
                );
            }
            $plans[] = "----------------------------------------";

        }
        return $plans;
    }
}
