<?php

namespace App\Command;

use Alma\API\RequestError;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaPaymentCreateCommand extends AbstractAlmaCommand
{
    protected static $defaultName = 'alma:payment:create';
    protected static $defaultDescription = 'Create payment with given informations';

    protected function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'A valid amount to perform payment (give it in cents)')
            ->addOption('installments-count', 'i', InputOption::VALUE_REQUIRED, 'pnx value', 3)
            ->addOption('return-url', null, InputOption::VALUE_REQUIRED, 'a website return URL after alma checkout')
            ->addOption(
                'cancel-url',
                null,
                InputOption::VALUE_REQUIRED,
                'a website return URL on customer cancel alma checkout'
            )
            ->addOption(
                'ipn-url',
                null,
                InputOption::VALUE_REQUIRED,
                'a website IPN callback URL for Alma events after checkout'
            )
            ->addOption(
                'custom-data',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'custom data for payment formatted as key:value',
                ['client:alma-cli']
            )
            ->addOption('payment-locale', 'l', InputOption::VALUE_REQUIRED, 'locale for payement', 'fr')
            ->addOption('order-reference', null, InputOption::VALUE_REQUIRED, 'unique identifier of your order')
            ->addOption('order-edit-url', null, InputOption::VALUE_REQUIRED, 'your website edit url for order')
            ->addOption('customer-edit-url', null, InputOption::VALUE_REQUIRED, 'you website edit url for customer')
            ->addOption('billing-city', null, InputOption::VALUE_REQUIRED, 'billing address "city" infos')
            ->addOption('billing-company', null, InputOption::VALUE_REQUIRED, 'billing address "company" infos')
            ->addOption('billing-country', null, InputOption::VALUE_REQUIRED, 'billing address "country" infos')
            ->addOption('billing-email', null, InputOption::VALUE_REQUIRED, 'billing address "email" infos')
            ->addOption('billing-first_name', null, InputOption::VALUE_REQUIRED, 'billing address "first_name" infos')
            ->addOption('billing-last_name', null, InputOption::VALUE_REQUIRED, 'billing address "last_name" infos')
            ->addOption('billing-line1', null, InputOption::VALUE_REQUIRED, 'billing address "line1" infos')
            ->addOption('billing-line2', null, InputOption::VALUE_REQUIRED, 'billing address "line2" infos')
            ->addOption('billing-phone', null, InputOption::VALUE_REQUIRED, 'billing address "phone" infos')
            ->addOption('billing-postal_code', null, InputOption::VALUE_REQUIRED, 'billing address "postal_code" infos')
            ->addOption('customer-city', null, InputOption::VALUE_REQUIRED, 'customer "city" infos')
            ->addOption('customer-company', null, InputOption::VALUE_REQUIRED, 'customer "company" infos')
            ->addOption('customer-country', null, InputOption::VALUE_REQUIRED, 'customer "country" infos')
            ->addOption('customer-email', null, InputOption::VALUE_REQUIRED, 'customer "email" infos')
            ->addOption('customer-first_name', null, InputOption::VALUE_REQUIRED, 'customer "first_name" infos')
            ->addOption('customer-last_name', null, InputOption::VALUE_REQUIRED, 'customer "last_name" infos')
            ->addOption('customer-line1', null, InputOption::VALUE_REQUIRED, 'customer "line1" infos')
            ->addOption('customer-line2', null, InputOption::VALUE_REQUIRED, 'customer "line2" infos')
            ->addOption('customer-phone', null, InputOption::VALUE_REQUIRED, 'customer "phone" infos')
            ->addOption('customer-postal_code', null, InputOption::VALUE_REQUIRED, 'customer "postal_code" infos')
            ->addOption('shipping-city', null, InputOption::VALUE_REQUIRED, 'shipping address "city" infos')
            ->addOption('shipping-company', null, InputOption::VALUE_REQUIRED, 'shipping address "company" infos')
            ->addOption('shipping-country', null, InputOption::VALUE_REQUIRED, 'shipping address "country" infos')
            ->addOption('shipping-email', null, InputOption::VALUE_REQUIRED, 'shipping address "email" infos')
            ->addOption('shipping-first_name', null, InputOption::VALUE_REQUIRED, 'shipping address "first_name" infos')
            ->addOption('shipping-last_name', null, InputOption::VALUE_REQUIRED, 'shipping address "last_name" infos')
            ->addOption('shipping-line1', null, InputOption::VALUE_REQUIRED, 'shipping address "line1" infos')
            ->addOption('shipping-line2', null, InputOption::VALUE_REQUIRED, 'shipping address "line2" infos')
            ->addOption('shipping-phone', null, InputOption::VALUE_REQUIRED, 'shipping address "phone" infos')
            ->addOption(
                'shipping-postal_code',
                null,
                InputOption::VALUE_REQUIRED,
                'shipping address "postal_code" infos'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount = intval($input->getArgument('amount'));
        if ($amount <= 0) {
            $this->io->error('Amount must be > 0');

            return self::INVALID;
        }
        $installmentsCount = intval($input->getOption('installments-count'));
        if ($installmentsCount <= 0) {
            $this->io->error('Installments count must be > 0');

            return self::INVALID;
        }

        $data = [
            'payment' => [
                'purchase_amount' => $amount,
//                'return_url'          => $input->getOption('return-url'),
//                'ipn_callback_url'    => $input->getOption('ipn-url'),
//                'customer_cancel_url' => $input->getOption('cancel-url'),
//                'installments_count'  => $installmentsCount,
//                'custom_data'         => $input->getOption('custom-data'),
                'locale'          => $input->getOption('payment-locale'),
                'billing_address'     => [
                    'first_name'  => $input->getOption('billing-first_name'),
                    'last_name'   => $input->getOption('billing-last_name'),
                    'email'       => $input->getOption('billing-email'),
                    'phone'       => $input->getOption('billing-phone'),
                    'company'     => $input->getOption('billing-company'),
                    'line1'       => $input->getOption('billing-line1'),
                    'line2'       => $input->getOption('billing-line2'),
                    'postal_code' => $input->getOption('billing-postal_code'),
                    'city'        => $input->getOption('billing-city'),
                ],
//                'shipping_address'    => [
//                    'first_name'  => $input->getOption('shipping-first_name'),
//                    'last_name'   => $input->getOption('shipping-last_name'),
//                    'email'       => $input->getOption('shipping-email'),
//                    'phone'       => $input->getOption('shipping-phone'),
//                    'company'     => $input->getOption('shipping-company'),
//                    'line1'       => $input->getOption('shipping-line1'),
//                    'line2'       => $input->getOption('shipping-line2'),
//                    'postal_code' => $input->getOption('shipping-postal_code'),
//                    'city'        => $input->getOption('shipping-city'),
//                ],
            ],
//            'customer'            => [
//                'first_name' => $input->getOption('customer-first_name'),
//                'last_name'  => $input->getOption('customer-last_name'),
//                'email'      => $input->getOption('customer-email'),
//                'phone'      => $input->getOption('customer-phone'),
//                'addresses'  => [
//                    [
//                        'first_name'  => $input->getOption('customer-first_name'),
//                        'last_name'   => $input->getOption('customer-last_name'),
//                        'email'       => $input->getOption('customer-email'),
//                        'phone'       => $input->getOption('customer-phone'),
//                        'company'     => $input->getOption('customer-company'),
//                        'line1'       => $input->getOption('customer-line1'),
//                        'line2'       => $input->getOption('customer-line2'),
//                        'postal_code' => $input->getOption('customer-postal_code'),
//                        'city'        => $input->getOption('customer-city'),
//                    ],
//                ],
//            ],
//            'order'   => [
//                'merchant_reference' => $input->getOption('order-reference'),
//                'merchant_url'       => $input->getOption('order-edit-url'),
//                'customer_url'       => $input->getOption('customer-edit-url'),
//            ],
        ];

        try {
            dump($data);
            $payment = $this->alma->payments->create($data);
        } catch (RequestError $e) {
            return $this->outputRequestError($e);

        }

        dump($payment);

        return self::SUCCESS;
    }

}
