<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Command\Meta\AbstractWriteAlmaCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaRefundCreateCommand extends AbstractWriteAlmaCommand
{
    protected static $defaultName = 'alma:refund:create';
    protected static $defaultDescription = 'make a full refund by payment ID';

    protected function configure(): void
    {
        $this
            ->addArgument(
                'ID',
                InputArgument::REQUIRED,
                'the payment id formatted as payment_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx or only xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx if you prefer'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $refunds = $this->almaClient->payments->fullRefund($input->getArgument('ID'))->refunds;
            $this->io->success('Here the following refunds from payment after your action');
            foreach ($refunds as $refund) {
                $this->outputFormatTable(get_object_vars($refund));
            }
        } catch (RequestError $e) {
            $this->io->error($e->getErrorMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
