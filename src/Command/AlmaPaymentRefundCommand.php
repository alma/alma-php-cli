<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Command\Meta\AbstractWriteAlmaCommand;
use App\Command\Meta\DisplayOutputPaymentTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaPaymentRefundCommand extends AbstractWriteAlmaCommand
{
    use DisplayOutputPaymentTrait;
    protected static $defaultName = 'alma:payment:refund';
    protected static $defaultDescription = 'Trigger a payment';

    protected function configure(): void
    {
        $this->configureOutputPaymentOptions();
        $this
            ->addArgument(
                'ID',
                InputArgument::REQUIRED,
                'the payment id formatted as payment_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx or only xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx if you prefer'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paymentId = $input->getArgument('ID');
        try {
            $payment = $this->almaClient->payments->fullRefund($paymentId);
        } catch (RequestError $e) {
            return $this->outputRequestError($e);
        }

        $this->outputPayment($payment, $input);
        return Command::SUCCESS;
    }
}
