<?php

namespace App\Command;

use Alma\API\RequestError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AlmaPaymentGetCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:payment:get';
    protected static $defaultDescription = 'Retrieve a payment by ID';

    protected function configure(): void
    {
        $this
            ->addArgument('ID', InputArgument::OPTIONAL, 'the payment id formatted as payment_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx or only xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx if you prefer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $payment = $this->alma->payments->fetch($input->getArgument('ID'));
            $this->io->title('Alma Payment from API');
            $this->outputKeyValueTable(get_object_vars($payment), ['shipping_address', 'customer', 'payment_plan']);
            $this->io->title('Alma Payment.customer from API');
            $this->outputKeyValueTable($payment->customer, ['addresses']);
            $this->io->title('Alma Payment.customer.addresses from API');
            $this->outputAddresses($payment->customer['addresses'], ['id', 'created']);
            $this->io->title('Alma Payment.payment_plan from API');
            $this->outputPaymentPlans($payment->payment_plan);
        } catch (RequestError $e) {
            return $this->outputRequestError($e);
        }

        return Command::SUCCESS;
    }
}