<?php

namespace App\Command;

use Alma\API\RequestError;
use App\Command\Meta\AbstractWriteAlmaCommand;
use App\Command\Meta\DisplayOutputAddressInterface;
use App\Command\Meta\DisplayOutputPayloadTrait;
use App\Command\Meta\DisplayOutputPaymentTrait;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlmaPaymentCreateCommand extends AbstractWriteAlmaCommand
{
    use DisplayOutputPaymentTrait;
    use DisplayOutputPayloadTrait;
    protected static $defaultName = 'alma:payment:create';
    protected static $defaultDescription = 'Create payment with given informations then output informations about payload & created payment';

    const ALLOWED_ORIGINS       = [
		"pos_link",
		"pos_sms",
		"pos_device",
		"online",
	];

    /**
     * @param string         $type
     * @param InputInterface $input
     *
     * @return array
     */
    protected function buildAddressFrom(string $type, InputInterface $input): array
    {
        $address = [];
        foreach (DisplayOutputAddressInterface::ADDRESSES_KEYS as $key) {
            $value = $input->getOption($this->getAddressOptionName($type, $key));
            if ($value) {
                $address[$key] = $value;
            }
        }

        return $address;
    }

    private function checkArrayValues(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        foreach ($array as $value) {
            if (is_null($value)) {
                return false;
            }
        }

        return true;
    }

    protected function configure(): void
    {
        $this->configureOutputPaymentOptions();
        $this->configureOutputPayloadOptions();
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'A valid amount to perform payment (give it in cents)')

            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'do not perform really the create payment action (useful if you just want to see the payload)'
            )
            ->addOption('installments', 'i', InputOption::VALUE_REQUIRED, 'pnx installments count', 3)
            ->addOption('deferred_days', 'd', InputOption::VALUE_REQUIRED, 'deferred days', 0)
            ->addOption(
                'deferred_trigger',
                null,
                InputOption::VALUE_NONE,
                'if it is a payment with deferred upon trigger'
            )
            ->addOption(
                'deferred_trigger_description',
                null,
                InputOption::VALUE_REQUIRED,
                'the deferred trigger description',
                'At Shipping'
            )
            ->addOption('payment-locale', 'l', InputOption::VALUE_REQUIRED, 'locale for payement', 'fr')
            ->addOption(
                'force-empty-payment-address',
                'F',
                InputOption::VALUE_NONE,
                'provide this flag if you don\'t want provide payment billing nor shipping address informations (empty billing billing address will be set in payload)'
            )
	        ->addOption('origin', null, InputOption::VALUE_REQUIRED, 'The payment origin (allowed values are [' . implode(", ", self::ALLOWED_ORIGINS) . ']', 'online')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'customer email')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'customer phone')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'customer first_name')
            ->addOption('birth-date', null, InputOption::VALUE_REQUIRED, 'customer birth_date')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'customer last_name')
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
                self::INPUT_OPTION_REQUIRED_ARRAY,
                'custom data for payment formatted as key:value',
                ['client:alma-cli']
            )
            ->addOption('order-reference', null, InputOption::VALUE_REQUIRED, 'unique identifier of your order')
            ->addOption('order-edit-url', null, InputOption::VALUE_REQUIRED, 'your website edit url for order')
            ->addOption('customer-edit-url', null, InputOption::VALUE_REQUIRED, 'you website edit url for customer')
        ;
        foreach (self::ADDRESSES_TYPES as $type) {
            foreach (DisplayOutputAddressInterface::ADDRESSES_KEYS as $key) {
                $this->addOption(
                    $this->getAddressOptionName($type, $key),
                    null,
                    InputOption::VALUE_REQUIRED,
                    sprintf('%s address "%s" info', $type, $key)
                );
            }
        }
    }

    /**
     * @param int            $amount
     * @param int            $installmentsCount
     * @param int            $deferredDays
     * @param InputInterface $input
     *
     * @return array[]
     * @throws Exception
     */
    protected function defaultPayload(
        int $amount,
        int $installmentsCount,
        int $deferredDays,
        InputInterface $input
    ): array
    {
        $customer = [
            'first_name' => $input->getOption('first-name'),
            'last_name'  => $input->getOption('last-name'),
            'birth_date'  => $input->getOption('birth-date'),
            'email'      => $input->getOption('email'),
            'phone'      => $input->getOption('phone'),
        ];

        $order = [
            'merchant_reference' => $input->getOption('order-reference'),
            'merchant_url'       => $input->getOption('order-edit-url'),
            'customer_url'       => $input->getOption('customer-edit-url'),
        ];

        $returnUrl  = $input->getOption('return-url');
        $ipnUrl     = $input->getOption('ipn-url');
        $cancelUrl  = $input->getOption('cancel-url');
        $customData = $input->getOption('custom-data');
        $locale     = $input->getOption('payment-locale');
        $origin     = $input->getOption('origin');
        if (!in_array($origin, self::ALLOWED_ORIGINS)) {
        	throw new Exception("'$origin': invalid Origin !!!");
        }
        $data       = [
	        'origin'             => $origin,
	        'payment' => [
                'purchase_amount'    => $amount,
                'installments_count' => $installmentsCount,
                'deferred_days'      => $deferredDays,
            ],
        ];
        if ($returnUrl) {
            $data['payment']['return_url'] = $returnUrl;
        }
        if ($ipnUrl) {
            $data['payment']['ipn_callback_url'] = $ipnUrl;
        }
        if ($cancelUrl) {
            $data['payment']['customer_cancel_url'] = $cancelUrl;
        }
        if ($customData) {
            $data['payment']['custom_data'] = $this->formatCustomData($customData);
        }
        if ($locale) {
            $data['payment']['locale'] = $locale;
        }
        if ($this->checkArrayValues($order)) {
            $data['order'] = $order;
        }
        if ($this->checkArrayValues($customer)) {
            $data['customer'] = $customer;
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount = intval($input->getArgument('amount'));
        if ($amount <= 0) {
            $this->io->error('Amount must be > 0');

            return self::INVALID;
        }
        $installmentsCount = intval($input->getOption('installments'));
        if ($installmentsCount <= 0) {
            $this->io->error('Installments count must be > 0');

            return self::INVALID;
        }
        $deferredDays = intval($input->getOption('deferred_days'));
        if ($deferredDays < 0) {
            $this->io->error('Installments count must be >= 0');

            return self::INVALID;
        }

        $data = $this->defaultPayload($amount, $installmentsCount, $deferredDays, $input);

        if ($input->getOption('deferred_trigger')) {
            $data['payment']['deferred']             = 'trigger';
            $data['payment']['deferred_description'] = $input->getOption('deferred_trigger_description');
        }

        $customerAddress   = $this->buildAddressFrom('customer', $input);
        $billingAddress    = $this->buildAddressFrom('billing', $input);
        $shippingAddress   = $this->buildAddressFrom('shipping', $input);
        $payloadAddresses  = [];
        $customerAddresses = [];
        if ($this->checkArrayValues($customerAddress)) {
            $customerAddresses[]                  = $customerAddress;
            $payloadAddresses['customer-address'] = $customerAddress;
        }
        if ($this->checkArrayValues($billingAddress)) {
            $customerAddresses[]                = $billingAddress;
            $data['payment']['billing_address'] = $billingAddress;
            $payloadAddresses['payment-billing-address'] = $billingAddress;
        }
        if ($this->checkArrayValues($shippingAddress)) {
            $customerAddresses[]                 = $shippingAddress;
            $data['payment']['shipping_address'] = $shippingAddress;
            $payloadAddresses['payment-shipping-address'] = $shippingAddress;
        }
        if (!empty($customerAddresses)) {
            $data['customer']['addresses'] = $customerAddresses;
        }
        if (!$this->checkArrayValues($shippingAddress)
            && !$this->checkArrayValues($billingAddress)
            && $input->getOption('force-empty-payment-address')
        ) {
            // force an empty billing address
            $data['payment']['billing_address'] = [];
            $payloadAddresses['payment-billing-address'] = [];
        }

        $this->outputPayload($input, $data, $payloadAddresses);

        if ($input->getOption('dry-run')) {
            $this->io->info('Command called with dry run ... we will not perform the payment creation');

            return self::SUCCESS;
        }
        try {
            $payment = $this->almaClient->payments->create($data);
        } catch (RequestError $e) {
            return $this->outputRequestError($e);

        }

        $this->outputPayment($payment, $input);

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function formatCustomData(array $customData): array
    {
        $formattedData = [];
        foreach ($customData as $data) {
            $split = explode(":", $data);
            if (sizeof($split) !== 2) {
                throw new Exception("bad custom data format ($data) => this must be formatted as 'key:value'");
            }
            $formattedData[$split[0]] = $split[1];
        }

        return $formattedData;
    }

}
