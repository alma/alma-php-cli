<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Command\Meta\AbstractWriteAlmaCommand;
use App\Command\Meta\DisplayOutputPaymentTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaPaymentCancelCommand extends AbstractWriteAlmaCommand
{
    use DisplayOutputPaymentTrait;

    protected static $defaultName = 'alma:payment:cancel';
    protected static $defaultDescription = 'Cancel an un-started payment';

    protected function configure(): void
    {
        $this->configureOutputPaymentOptions();
        $this
            ->addArgument(
                'ID',
                InputArgument::REQUIRED,
                'the payment id formatted as payment_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx or only xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx if you prefer'
            )
        ;
    }

    /**
     * @throws RequestError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paymentId = $input->getArgument('ID');
        $this->almaClient->payments->cancel($paymentId);

        $this->io->success(sprintf("Payment '%s' successfully canceled", $paymentId));

        return Command::SUCCESS;
    }
}
